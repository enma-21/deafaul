<?php
/**
 * Plugin Name: M2 Mercado Libre Integration
 * Description: OAuth, token refresh, notification callback and bulk recategorization for Mercado Libre (multi-country).
 * Version: 0.3.0
 * Author: M2 Base
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class M2_Mercado_Libre_Integration {
    private const OPTION = 'm2_ml_settings';
    private const NOTIFICATIONS_OPTION = 'm2_ml_notifications';
    private const RECAT_OPTION = 'm2_ml_recat_queue';
    private const RECAT_BATCH_SIZE = 10;
    private const RECAT_MAX_ATTEMPTS = 5;
    private const STATE_PREFIX = 'm2_ml_oauth_state_';
    private const TOKEN_URL = 'https://api.mercadolibre.com/oauth/token';
    private const API_URL = 'https://api.mercadolibre.com';

    /**
     * Mercado Libre's login/authorization page is country-specific: it rejects
     * credentials from a different country's site even though the app (client_id)
     * itself works globally for the token exchange. This maps the Site ID setting
     * to the correct auth.mercadolibre.<domain> to show the right login page.
     */
    private const AUTH_DOMAINS = [
        'MLA' => 'com.ar',
        'MCO' => 'com.co',
        'MLC' => 'cl',
        'MLM' => 'com.mx',
        'MLU' => 'com.uy',
        'MPE' => 'com.pe',
        'MEC' => 'com.ec',
        'MLV' => 'com.ve',
        'MCR' => 'co.cr',
        'MPA' => 'com.pa',
        'MPY' => 'com.py',
        'MBO' => 'com.bo',
        'MGT' => 'com.gt',
        'MHN' => 'com.hn',
        'MNI' => 'com.ni',
        'MSV' => 'com.sv',
        'MDO' => 'com.do',
    ];

    public static function init(): void {
        add_action('init', [self::class, 'register_routes']);
        add_filter('query_vars', [self::class, 'query_vars']);
        add_action('template_redirect', [self::class, 'handle_routes']);
        add_action('rest_api_init', [self::class, 'register_rest_routes']);
        add_action('admin_menu', [self::class, 'admin_menu']);
        add_action('admin_post_m2_ml_save_settings', [self::class, 'save_settings']);
        add_action('admin_post_m2_ml_recat_upload', [self::class, 'recat_upload']);
        add_action('admin_post_m2_ml_recat_reset', [self::class, 'recat_reset']);
        add_action('admin_post_m2_ml_recat_download', [self::class, 'recat_download']);
    }

    public static function activate(): void {
        self::register_routes();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function register_routes(): void {
        add_rewrite_rule(
            '^mercadolibre/(authorize|callback|notifications)/?$',
            'index.php?m2_ml_route=$matches[1]',
            'top'
        );
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'm2_ml_route';
        return $vars;
    }

    private static function settings(): array {
        return wp_parse_args(get_option(self::OPTION, []), [
            'client_id' => '',
            'client_secret' => '',
            'site_id' => 'MCO',
            'access_token' => '',
            'refresh_token' => '',
            'expires_at' => 0,
            'user_id' => '',
        ]);
    }

    private static function crypto_key(): string {
        return hash('sha256', wp_salt('auth') . '|m2-mercadolibre', true);
    }

    private static function encrypt_secret(string $value): string {
        if ($value === '' || !function_exists('openssl_encrypt')) {
            return $value;
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt(
            $value,
            'aes-256-cbc',
            self::crypto_key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return 'enc:' . base64_encode($iv . $cipher);
    }

    private static function decrypt_secret(string $value): string {
        if ($value === '' || strpos($value, 'enc:') !== 0 || !function_exists('openssl_decrypt')) {
            return $value;
        }

        $decoded = base64_decode(substr($value, 4), true);
        if ($decoded === false || strlen($decoded) <= 16) {
            return '';
        }

        $iv = substr($decoded, 0, 16);
        $cipher = substr($decoded, 16);
        $plain = openssl_decrypt(
            $cipher,
            'aes-256-cbc',
            self::crypto_key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return is_string($plain) ? $plain : '';
    }

    private static function client_secret(): string {
        $settings = self::settings();
        return self::decrypt_secret((string) $settings['client_secret']);
    }

    private static function redirect_url(): string {
        return home_url('/mercadolibre/callback');
    }

    private static function notifications_url(): string {
        return home_url('/mercadolibre/notifications');
    }

    private static function authorize_url(): string {
        return home_url('/mercadolibre/authorize');
    }

    private static function auth_login_url(): string {
        $settings = self::settings();
        $site_id = strtoupper(trim((string) $settings['site_id']));
        $domain = self::AUTH_DOMAINS[$site_id] ?? 'com.ar';
        return "https://auth.mercadolibre.{$domain}/authorization";
    }

    private static function base64url(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function handle_routes(): void {
        $route = get_query_var('m2_ml_route');
        if (!$route) {
            return;
        }

        nocache_headers();

        switch ($route) {
            case 'authorize':
                self::handle_authorize();
                break;
            case 'callback':
                self::handle_callback();
                break;
            case 'notifications':
                self::handle_notifications();
                break;
        }
        exit;
    }

    private static function handle_authorize(): void {
        $settings = self::settings();
        $client_id = trim((string) $settings['client_id']);

        if ($client_id === '') {
            self::render_message(
                'Mercado Libre no está configurado',
                'Primero guarda el Client ID en Ajustes → Mercado Libre.'
            );
        }

        try {
            $state = wp_generate_uuid4();
            $verifier = self::base64url(random_bytes(48));
        } catch (Throwable $error) {
            self::render_message('No se pudo iniciar OAuth', 'El servidor no pudo generar un valor seguro para PKCE.', 500);
        }

        $challenge = self::base64url(hash('sha256', $verifier, true));
        set_transient(self::STATE_PREFIX . hash('sha256', $state), [
            'verifier' => $verifier,
            'created_at' => time(),
        ], 10 * MINUTE_IN_SECONDS);

        $url = add_query_arg([
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => self::redirect_url(),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], self::auth_login_url());

        wp_redirect($url, 302, 'M2 Mercado Libre');
        exit;
    }

    private static function handle_callback(): void {
        if (isset($_GET['error'])) {
            $description = isset($_GET['error_description'])
                ? sanitize_text_field(wp_unslash($_GET['error_description']))
                : 'El usuario canceló o Mercado Libre rechazó la autorización.';
            self::render_message('Autorización cancelada', $description, 400);
        }

        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        if ($code === '' || $state === '') {
            self::render_message('Callback incompleto', 'Mercado Libre no envió code y state.', 400);
        }

        $transient_key = self::STATE_PREFIX . hash('sha256', $state);
        $oauth_state = get_transient($transient_key);
        delete_transient($transient_key);
        if (!is_array($oauth_state) || empty($oauth_state['verifier'])) {
            self::render_message('State inválido o vencido', 'Vuelve a iniciar la conexión desde Ajustes → Mercado Libre.', 400);
        }

        $settings = self::settings();
        $client_id = trim((string) $settings['client_id']);
        $client_secret = self::client_secret();
        if ($client_id === '' || $client_secret === '') {
            self::render_message('Faltan credenciales', 'Configura Client ID y Client Secret antes de autorizar.', 500);
        }

        $response = wp_remote_post(self::TOKEN_URL, [
            'timeout' => 30,
            'headers' => ['Accept' => 'application/json'],
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => self::redirect_url(),
                'code_verifier' => $oauth_state['verifier'],
            ],
        ]);

        if (is_wp_error($response)) {
            self::render_message('Error conectando con Mercado Libre', $response->get_error_message(), 502);
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || !is_array($body) || empty($body['access_token'])) {
            self::render_message(
                'Mercado Libre rechazó la autorización',
                'Revisa el Client ID, Client Secret y que la Redirect URI coincida exactamente.',
                502
            );
        }

        $settings['access_token'] = self::encrypt_secret((string) $body['access_token']);
        $settings['refresh_token'] = self::encrypt_secret((string) ($body['refresh_token'] ?? ''));
        $settings['expires_at'] = time() + max(60, (int) ($body['expires_in'] ?? 21600));
        $settings['user_id'] = sanitize_text_field((string) ($body['user_id'] ?? ''));
        update_option(self::OPTION, $settings, false);

        self::render_message(
            'Mercado Libre conectado',
            'La autorización fue completada. Ya puedes cerrar esta ventana y volver a Ajustes → Mercado Libre.'
        );
    }

    private static function handle_notifications(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            status_header(405);
            wp_send_json(['ok' => false, 'message' => 'Use POST.'], 405);
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode((string) $raw, true);
        if (!is_array($payload)) {
            status_header(400);
            wp_send_json(['ok' => false, 'message' => 'JSON inválido.'], 400);
        }

        $notifications = get_option(self::NOTIFICATIONS_OPTION, []);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        array_unshift($notifications, [
            'received_at' => gmdate('c'),
            'payload' => $payload,
        ]);
        update_option(self::NOTIFICATIONS_OPTION, array_slice($notifications, 0, 50), false);

        wp_send_json(['ok' => true], 200);
    }

    private static function access_token(): string {
        $settings = self::settings();
        $access_token = self::decrypt_secret((string) $settings['access_token']);
        $expires_at = (int) $settings['expires_at'];

        if ($access_token !== '' && $expires_at > time() + 60) {
            return $access_token;
        }

        $refresh_token = self::decrypt_secret((string) $settings['refresh_token']);
        if ($refresh_token === '' || (string) $settings['client_id'] === '' || self::client_secret() === '') {
            return $access_token;
        }

        $response = wp_remote_post(self::TOKEN_URL, [
            'timeout' => 30,
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => (string) $settings['client_id'],
                'client_secret' => self::client_secret(),
                'refresh_token' => $refresh_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $access_token;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body) || empty($body['access_token'])) {
            return $access_token;
        }

        $settings['access_token'] = self::encrypt_secret((string) $body['access_token']);
        if (!empty($body['refresh_token'])) {
            $settings['refresh_token'] = self::encrypt_secret((string) $body['refresh_token']);
        }
        $settings['expires_at'] = time() + max(60, (int) ($body['expires_in'] ?? 21600));
        update_option(self::OPTION, $settings, false);

        return (string) $body['access_token'];
    }

    public static function register_rest_routes(): void {
        register_rest_route('m2-mercadolibre/v1', '/status', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => [self::class, 'admin_permission'],
            'callback' => function (): array {
                $settings = self::settings();
                return [
                    'configured' => (string) $settings['client_id'] !== '' && self::client_secret() !== '',
                    'connected' => self::access_token() !== '',
                    'site_id' => $settings['site_id'],
                    'user_id' => $settings['user_id'],
                    'callback_url' => self::redirect_url(),
                    'notifications_url' => self::notifications_url(),
                ];
            },
        ]);
    }

    public static function admin_permission(): bool {
        return current_user_can('manage_options');
    }

    public static function admin_menu(): void {
        add_options_page(
            'Mercado Libre',
            'Mercado Libre',
            'manage_options',
            'm2-mercadolibre',
            [self::class, 'settings_page']
        );
    }

    public static function save_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Mercado Libre', ['response' => 403]);
        }
        check_admin_referer('m2_ml_save_settings');

        $settings = self::settings();
        $settings['client_id'] = sanitize_text_field(wp_unslash($_POST['client_id'] ?? ''));
        $settings['site_id'] = strtoupper(sanitize_text_field(wp_unslash($_POST['site_id'] ?? 'MCO')));

        $client_secret = trim((string) wp_unslash($_POST['client_secret'] ?? ''));
        if ($client_secret !== '') {
            $settings['client_secret'] = self::encrypt_secret($client_secret);
        }

        update_option(self::OPTION, $settings, false);
        wp_safe_redirect(add_query_arg([
            'page' => 'm2-mercadolibre',
            'updated' => '1',
        ], admin_url('options-general.php')));
        exit;
    }

    /* ---------------------------------------------------------------
     * Recategorización masiva
     * ------------------------------------------------------------- */

    private static function redirect_settings(array $args): void {
        wp_safe_redirect(add_query_arg(array_merge(['page' => 'm2-mercadolibre'], $args), admin_url('options-general.php')));
        exit;
    }

    private static function find_column(array $normalized_headers, array $candidates): ?int {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $normalized_headers, true);
            if ($index !== false) {
                return (int) $index;
            }
        }
        return null;
    }

    public static function recat_upload(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Mercado Libre', ['response' => 403]);
        }
        check_admin_referer('m2_ml_recat_upload');

        if (empty($_FILES['recat_csv']['tmp_name']) || !is_uploaded_file($_FILES['recat_csv']['tmp_name'])) {
            self::redirect_settings(['recat_error' => rawurlencode('No se recibió ningún archivo CSV.')]);
        }

        $handle = fopen($_FILES['recat_csv']['tmp_name'], 'r');
        if (!$handle) {
            self::redirect_settings(['recat_error' => rawurlencode('No se pudo leer el archivo.')]);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            self::redirect_settings(['recat_error' => rawurlencode('El CSV está vacío.')]);
        }

        $normalized = array_map(static function ($h) {
            return strtolower(trim((string) $h));
        }, $header);

        $item_candidates = ['id', 'item id', 'id publicacion', 'id publicación', 'mlb', 'mlv', 'item', 'item_id'];
        $category_candidates = [
            'id categoria destino', 'id categoría destino', 'categoria destino id',
            'category_id_destino', 'nueva categoria id', 'category_id',
        ];

        $item_col = self::find_column($normalized, $item_candidates);
        $category_col = self::find_column($normalized, $category_candidates);

        if ($item_col === null || $category_col === null) {
            fclose($handle);
            self::redirect_settings([
                'recat_error' => rawurlencode(
                    'No se detectaron las columnas de ID y categoría destino. Encabezados encontrados: ' . implode(', ', $header)
                ),
            ]);
        }

        $queue = [];
        while (($row = fgetcsv($handle)) !== false) {
            $item_id = isset($row[$item_col]) ? trim((string) $row[$item_col]) : '';
            $category_id = isset($row[$category_col]) ? trim((string) $row[$category_col]) : '';
            if ($item_id === '' || $category_id === '') {
                continue;
            }
            $queue[] = [
                'item_id' => $item_id,
                'category_id' => $category_id,
                'status' => 'pending',
                'http_status' => null,
                'detail' => '',
                'missing_attributes' => '',
                'attempts' => 0,
            ];
        }
        fclose($handle);

        if (empty($queue)) {
            self::redirect_settings(['recat_error' => rawurlencode('El CSV no tiene filas válidas.')]);
        }

        update_option(self::RECAT_OPTION, $queue, false);
        self::redirect_settings(['recat_uploaded' => count($queue)]);
    }

    public static function recat_reset(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Mercado Libre', ['response' => 403]);
        }
        check_admin_referer('m2_ml_recat_reset');
        delete_option(self::RECAT_OPTION);
        self::redirect_settings(['recat_reset' => '1']);
    }

    public static function recat_download(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Mercado Libre', ['response' => 403]);
        }
        check_admin_referer('m2_ml_recat_download');

        $queue = get_option(self::RECAT_OPTION, []);
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultado_recategorizacion.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['item_id', 'category_id', 'status', 'http_status', 'detail', 'missing_attributes']);
        foreach ((array) $queue as $row) {
            fputcsv($out, [
                $row['item_id'] ?? '',
                $row['category_id'] ?? '',
                $row['status'] ?? '',
                $row['http_status'] ?? '',
                $row['detail'] ?? '',
                $row['missing_attributes'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    private static function extract_missing_attributes(array $body): string {
        $causes = $body['cause'] ?? [];
        if (!is_array($causes) || empty($causes)) {
            return '';
        }
        $parts = [];
        foreach ($causes as $cause) {
            if (!is_array($cause)) {
                $parts[] = (string) $cause;
                continue;
            }
            $code = $cause['code'] ?? '';
            $message = $cause['message'] ?? '';
            $references = $cause['references'] ?? ($cause['attribute'] ?? '');
            if (is_array($references)) {
                $references = implode(',', $references);
            }
            $parts[] = trim("[$code] $message $references");
        }
        return implode(' | ', $parts);
    }

    private static function recat_process_batch(): void {
        $queue = get_option(self::RECAT_OPTION, []);
        if (empty($queue)) {
            return;
        }

        $token = self::access_token();
        $processed = 0;

        foreach ($queue as $i => &$row) {
            if ($processed >= self::RECAT_BATCH_SIZE) {
                break;
            }
            if (($row['status'] ?? 'pending') !== 'pending') {
                continue;
            }

            if ($token === '') {
                $row['status'] = 'error';
                $row['detail'] = 'No hay access token válido. Conecta la cuenta primero.';
                $processed++;
                continue;
            }

            $response = wp_remote_request(self::API_URL . '/items/' . rawurlencode($row['item_id']), [
                'method' => 'PUT',
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode(['category_id' => $row['category_id']]),
            ]);

            $processed++;
            usleep(300000);

            if (is_wp_error($response)) {
                $row['attempts'] = (int) ($row['attempts'] ?? 0) + 1;
                $row['detail'] = $response->get_error_message();
                if ($row['attempts'] >= self::RECAT_MAX_ATTEMPTS) {
                    $row['status'] = 'error';
                }
                continue;
            }

            $status = wp_remote_retrieve_response_code($response);

            if ($status >= 200 && $status < 300) {
                $row['status'] = 'ok';
                $row['http_status'] = $status;
                $row['detail'] = 'OK';
                continue;
            }

            if ($status === 429 || $status >= 500) {
                $row['attempts'] = (int) ($row['attempts'] ?? 0) + 1;
                $row['http_status'] = $status;
                $row['detail'] = 'Rate limit o error de servidor.';
                if ($row['attempts'] >= self::RECAT_MAX_ATTEMPTS) {
                    $row['status'] = 'error';
                }
                continue;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $row['status'] = 'error';
            $row['http_status'] = $status;
            $row['detail'] = is_array($body) && !empty($body['message']) ? $body['message'] : 'Error desconocido.';
            $row['missing_attributes'] = self::extract_missing_attributes(is_array($body) ? $body : []);
        }
        unset($row);

        update_option(self::RECAT_OPTION, $queue, false);
    }

    private static function recat_counts(array $queue): array {
        $counts = ['total' => count($queue), 'pending' => 0, 'ok' => 0, 'error' => 0];
        foreach ($queue as $row) {
            $status = $row['status'] ?? 'pending';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        return $counts;
    }

    /* ---------------------------------------------------------------
     * Pantalla de configuración
     * ------------------------------------------------------------- */

    public static function settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::settings();
        $connected = self::access_token() !== '';

        $recat_queue = get_option(self::RECAT_OPTION, []);
        if (!is_array($recat_queue)) {
            $recat_queue = [];
        }
        if (!empty($recat_queue)) {
            self::recat_process_batch();
            $recat_queue = get_option(self::RECAT_OPTION, []);
        }
        $recat_counts = self::recat_counts($recat_queue);
        ?>
        <div class="wrap">
            <h1>Mercado Libre</h1>
            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>
            <?php endif; ?>
            <p>Esta integración usa OAuth y PKCE. Los tokens se almacenan cifrados usando las claves de WordPress.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="m2_ml_save_settings">
                <?php wp_nonce_field('m2_ml_save_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="m2-ml-client-id">Client ID</label></th>
                        <td><input id="m2-ml-client-id" name="client_id" type="text" class="regular-text" value="<?php echo esc_attr($settings['client_id']); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="m2-ml-client-secret">Client Secret</label></th>
                        <td>
                            <input id="m2-ml-client-secret" name="client_secret" type="password" class="regular-text" value="" autocomplete="new-password">
                            <p class="description">Déjalo vacío para conservar el actual.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="m2-ml-site-id">Site ID</label></th>
                        <td>
                            <input id="m2-ml-site-id" name="site_id" type="text" class="regular-text" value="<?php echo esc_attr($settings['site_id']); ?>" maxlength="3">
                            <p class="description">
                                País de la cuenta que vas a conectar (no el país donde se creó la app). Determina la pantalla de login al pulsar "Conectar Mercado Libre" — Mercado Libre rechaza el login si no coincide con el país real de la cuenta.
                                Ej: <code>MCO</code> = Colombia, <code>MLV</code> = Venezuela, <code>MLA</code> = Argentina, <code>MLM</code> = México.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Guardar configuración'); ?>
            </form>

            <hr>
            <h2>Estado</h2>
            <p><strong><?php echo $connected ? 'Conectado' : 'Sin conectar'; ?></strong></p>
            <?php if (!$connected) : ?>
                <p>Después de guardar Client ID y Client Secret, abre este enlace para autorizar la cuenta:</p>
                <p><a class="button button-primary" target="_blank" rel="noopener" href="<?php echo esc_url(self::authorize_url()); ?>">Conectar Mercado Libre</a></p>
            <?php endif; ?>

            <h2>URLs para la aplicación de Mercado Libre</h2>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                    <tr><td><strong>Redirect URI</strong></td><td><code><?php echo esc_html(self::redirect_url()); ?></code></td></tr>
                    <tr><td><strong>Notifications callback</strong></td><td><code><?php echo esc_html(self::notifications_url()); ?></code></td></tr>
                </tbody>
            </table>

            <hr>
            <h2>Recategorización masiva</h2>
            <p>Sube un CSV con dos columnas: ID de la publicación e ID de categoría destino (exporta la pestaña "Planilla de Mapeo" del Excel como CSV).</p>

            <?php if (isset($_GET['recat_error'])) : ?>
                <div class="notice notice-error"><p><?php echo esc_html(wp_unslash($_GET['recat_error'])); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['recat_uploaded'])) : ?>
                <div class="notice notice-success"><p><?php echo (int) $_GET['recat_uploaded']; ?> publicaciones cargadas en la cola.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['recat_reset'])) : ?>
                <div class="notice notice-success"><p>Cola de recategorización reiniciada.</p></div>
            <?php endif; ?>

            <?php if (!$connected) : ?>
                <div class="notice notice-warning"><p>Conecta la cuenta de Mercado Libre (arriba) antes de procesar la recategorización.</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="margin-bottom:1em;">
                <input type="hidden" name="action" value="m2_ml_recat_upload">
                <?php wp_nonce_field('m2_ml_recat_upload'); ?>
                <input type="file" name="recat_csv" accept=".csv" required>
                <?php submit_button('Cargar CSV y comenzar', 'primary', 'submit', false); ?>
            </form>

            <?php if ($recat_counts['total'] > 0) : ?>
                <table class="widefat striped" style="max-width:500px;margin-bottom:1em;">
                    <tbody>
                        <tr><td>Total</td><td><?php echo (int) $recat_counts['total']; ?></td></tr>
                        <tr><td>Pendientes</td><td><?php echo (int) $recat_counts['pending']; ?></td></tr>
                        <tr><td>OK</td><td><?php echo (int) $recat_counts['ok']; ?></td></tr>
                        <tr><td>Con error</td><td><?php echo (int) $recat_counts['error']; ?></td></tr>
                    </tbody>
                </table>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:8px;">
                    <input type="hidden" name="action" value="m2_ml_recat_download">
                    <?php wp_nonce_field('m2_ml_recat_download'); ?>
                    <?php submit_button('Descargar CSV de resultados', 'secondary', 'submit', false); ?>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;" onsubmit="return confirm('¿Seguro? Esto borra la cola actual.');">
                    <input type="hidden" name="action" value="m2_ml_recat_reset">
                    <?php wp_nonce_field('m2_ml_recat_reset'); ?>
                    <?php submit_button('Reiniciar cola', 'delete', 'submit', false); ?>
                </form>

                <h3>Últimas filas en la cola</h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Item ID</th><th>Categoría destino</th><th>Estado</th>
                            <th>HTTP</th><th>Detalle</th><th>Atributos faltantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recat_queue, 0, 30) as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['item_id'] ?? ''); ?></td>
                                <td><?php echo esc_html($row['category_id'] ?? ''); ?></td>
                                <td><?php echo esc_html($row['status'] ?? ''); ?></td>
                                <td><?php echo esc_html((string) ($row['http_status'] ?? '')); ?></td>
                                <td><?php echo esc_html($row['detail'] ?? ''); ?></td>
                                <td><?php echo esc_html($row['missing_attributes'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($recat_counts['pending'] > 0) : ?>
                    <p><em>Procesando automáticamente en lotes de <?php echo (int) self::RECAT_BATCH_SIZE; ?>… esta página se recargará sola cada pocos segundos hasta terminar. No la cierres.</em></p>
                    <script>setTimeout(function () { window.location.reload(); }, 4000);</script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_message(string $title, string $message, int $status = 200): void {
        status_header($status);
        wp_die(
            '<p>' . esc_html($message) . '</p>',
            esc_html($title),
            ['response' => $status]
        );
    }
}

M2_Mercado_Libre_Integration::init();
register_activation_hook(__FILE__, ['M2_Mercado_Libre_Integration', 'activate']);
register_deactivation_hook(__FILE__, ['M2_Mercado_Libre_Integration', 'deactivate']);
