<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Webhook público para WhatsApp Cloud API (Meta). No usa register_rest_route()
 * porque un tercero externo sin sesión de WordPress lo llama directamente;
 * se sigue el mismo patrón de add_rewrite_rule()+template_redirect() que ya
 * usa m2-mercadolibre-integration para su webhook de notificaciones.
 */
final class M2Wab_Webhook {

    public static function init(): void {
        add_action('init', [self::class, 'register_routes']);
        add_filter('query_vars', [self::class, 'query_vars']);
        add_action('template_redirect', [self::class, 'handle_routes']);
    }

    public static function register_routes(): void {
        add_rewrite_rule('^whatsapp-bot/webhook/?$', 'index.php?m2wab_route=webhook', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'm2wab_route';
        return $vars;
    }

    public static function webhook_url(): string {
        return home_url('/whatsapp-bot/webhook');
    }

    public static function handle_routes(): void {
        if (get_query_var('m2wab_route') !== 'webhook') {
            return;
        }

        nocache_headers();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET') {
            self::handle_verify();
        } elseif ($method === 'POST') {
            self::handle_incoming();
        } else {
            status_header(405);
        }
        exit;
    }

    /**
     * Meta convierte los puntos de "hub.mode"/"hub.verify_token"/"hub.challenge"
     * en guiones bajos al llegar a $_GET, porque PHP transforma automáticamente
     * los puntos en los nombres de variables de query string.
     */
    private static function handle_verify(): void {
        $modo = isset($_GET['hub_mode']) ? sanitize_text_field(wp_unslash($_GET['hub_mode'])) : '';
        $token = isset($_GET['hub_verify_token']) ? sanitize_text_field(wp_unslash($_GET['hub_verify_token'])) : '';
        $challenge = isset($_GET['hub_challenge']) ? sanitize_text_field(wp_unslash($_GET['hub_challenge'])) : '';

        $token_configurado = M2Wab_Settings::verify_token();

        if ($modo === 'subscribe' && $token_configurado !== '' && $token !== '' && hash_equals($token_configurado, $token)) {
            header('Content-Type: text/plain');
            echo $challenge;
            return;
        }

        status_header(403);
        echo 'Verificacion fallida.';
    }

    private static function handle_incoming(): void {
        $raw = (string) file_get_contents('php://input');

        if (!self::validar_firma($raw)) {
            status_header(403);
            wp_send_json(['ok' => false, 'message' => 'Firma invalida.'], 403);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            status_header(400);
            wp_send_json(['ok' => false, 'message' => 'JSON invalido.'], 400);
        }

        self::procesar_payload($payload);

        wp_send_json(['ok' => true], 200);
    }

    private static function validar_firma(string $raw): bool {
        $app_secret = M2Wab_Settings::app_secret();
        $header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

        if ($app_secret === '' || strpos($header, 'sha256=') !== 0) {
            return false;
        }

        $esperada = hash_hmac('sha256', $raw, $app_secret);
        $recibida = substr($header, 7);

        return hash_equals($esperada, $recibida);
    }

    private static function procesar_payload(array $payload): void {
        $entries = $payload['entry'] ?? [];
        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            $changes = is_array($entry) ? ($entry['changes'] ?? []) : [];
            if (!is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                $value = is_array($change) ? ($change['value'] ?? []) : [];
                $mensajes = is_array($value) ? ($value['messages'] ?? []) : [];

                // Meta también manda "statuses" (confirmaciones de entrega/lectura) en el
                // mismo webhook; se ignoran, solo interesan los mensajes entrantes reales.
                if (!is_array($mensajes) || empty($mensajes)) {
                    continue;
                }

                $contactos = is_array($value['contacts'] ?? null) ? $value['contacts'] : [];
                $nombre_perfil = '';
                if (!empty($contactos[0]['profile']['name'])) {
                    $nombre_perfil = sanitize_text_field((string) $contactos[0]['profile']['name']);
                }

                foreach ($mensajes as $mensaje) {
                    if (is_array($mensaje)) {
                        self::procesar_mensaje($mensaje, $nombre_perfil);
                    }
                }
            }
        }
    }

    private static function procesar_mensaje(array $mensaje, string $nombre_perfil): void {
        $wa_id = (string) ($mensaje['from'] ?? '');
        $wa_message_id = (string) ($mensaje['id'] ?? '');
        $tipo = (string) ($mensaje['type'] ?? '');

        if ($wa_id === '' || $wa_message_id === '') {
            return;
        }

        // Meta reintenta la entrega del webhook si no confirmamos rápido con 200;
        // sin esta comprobación se procesaría y respondería el mismo mensaje varias veces.
        if (M2Wab_Repository::ya_procesado($wa_message_id)) {
            return;
        }

        $texto = $tipo === 'text' ? (string) ($mensaje['text']['body'] ?? '') : '';
        M2Wab_Repository::guardar_mensaje($wa_id, 'in', $texto !== '' ? $texto : "[{$tipo}]", $wa_message_id, $tipo);

        if ($texto === '') {
            $respuesta = 'Por ahora solo puedo leer mensajes de texto. Escríbeme qué repuesto necesitas.';
            $id_saliente = M2Wab_Client::enviar_texto($wa_id, $respuesta);
            M2Wab_Repository::guardar_mensaje($wa_id, 'out', $respuesta, $id_saliente, 'text');
            return;
        }

        $conversacion = M2Wab_Repository::obtener_o_crear_conversacion($wa_id, $nombre_perfil);
        $contexto = M2Wab_Repository::obtener_contexto($conversacion);

        [$respuesta, $contexto_nuevo] = M2Wab_Responder::responder($texto, $contexto);
        M2Wab_Repository::guardar_contexto($wa_id, $contexto_nuevo);

        $id_saliente = M2Wab_Client::enviar_texto($wa_id, $respuesta);
        M2Wab_Repository::guardar_mensaje($wa_id, 'out', $respuesta, $id_saliente, 'text');
    }
}
