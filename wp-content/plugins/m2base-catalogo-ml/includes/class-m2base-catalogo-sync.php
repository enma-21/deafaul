<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sincroniza el catálogo del vendedor desde Mercado Libre a las tablas
 * propias. Sin cron: avanza un lote cada vez que se carga la página de
 * ajustes (mismo patrón ya usado en m2-mercadolibre-integration para la
 * recategorización masiva), así que es tolerante a que se cierre la pestaña
 * a mitad de camino — retoma donde quedó.
 */
final class M2Base_Catalogo_ML_Sync {

    const STATE_OPTION = 'm2mlc_sync_state';
    const CATEGORY_CACHE_OPTION = 'm2mlc_category_names';
    const API_URL = 'https://api.mercadolibre.com';
    const LISTING_PAGES_PER_TICK = 5;
    const DETAIL_CHUNKS_PER_TICK = 10;
    const MULTIGET_SIZE = 20;
    const MAX_ATTEMPTS = 5;

    private static function ml_access_token(): string {
        return class_exists('M2_Mercado_Libre_Integration') ? M2_Mercado_Libre_Integration::access_token() : '';
    }

    private static function ml_seller_user_id(): string {
        return class_exists('M2_Mercado_Libre_Integration') ? M2_Mercado_Libre_Integration::seller_user_id() : '';
    }

    public static function state(): array {
        return wp_parse_args(get_option(self::STATE_OPTION, []), [
            'run_id' => 0,
            'phase' => 'done',
            'scroll_id' => '',
            'test_limit' => 0,
            'total_listed' => 0,
            'started_at_gmt' => '',
        ]);
    }

    public static function esta_activo(): bool {
        return self::state()['phase'] !== 'done';
    }

    public static function iniciar(int $test_limit = 0): int {
        global $wpdb;
        $table_runs = M2Base_Catalogo_ML_Schema::table_runs();
        $wpdb->insert($table_runs, [
            'phase' => 'listing',
            'started_at' => current_time('mysql'),
            'started_by' => get_current_user_id(),
        ]);
        $run_id = (int) $wpdb->insert_id;

        update_option(self::STATE_OPTION, [
            'run_id' => $run_id,
            'phase' => 'listing',
            'scroll_id' => '',
            'test_limit' => $test_limit,
            'total_listed' => 0,
            'started_at_gmt' => current_time('mysql', true),
        ], false);

        return $run_id;
    }

    public static function reiniciar(): void {
        delete_option(self::STATE_OPTION);
    }

    public static function process_batch(): void {
        $state = self::state();
        if ($state['phase'] === 'listing') {
            self::process_listing_batch($state);
        } elseif ($state['phase'] === 'detail') {
            self::process_detail_batch($state);
        }
    }

    private static function process_listing_batch(array $state): void {
        global $wpdb;
        $token = self::ml_access_token();
        $seller_id = self::ml_seller_user_id();
        if ($token === '' || $seller_id === '') {
            return;
        }

        $queue_table = M2Base_Catalogo_ML_Schema::table_queue();
        $scroll_id = (string) ($state['scroll_id'] ?? '');
        $test_limit = (int) ($state['test_limit'] ?? 0);
        $total_listed = (int) ($state['total_listed'] ?? 0);
        $terminado = false;

        for ($page = 0; $page < self::LISTING_PAGES_PER_TICK; $page++) {
            $limit = 100;
            if ($test_limit > 0) {
                $restante = $test_limit - $total_listed;
                if ($restante <= 0) {
                    $terminado = true;
                    break;
                }
                $limit = min(100, $restante);
            }

            $url = self::API_URL . '/users/' . rawurlencode($seller_id) . '/items/search?' . http_build_query([
                'search_type' => 'scan',
                'limit' => $limit,
                'status' => 'active',
                'scroll_id' => $scroll_id,
            ]);

            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);
            usleep(300000);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                break;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $ids = is_array($body) ? ($body['results'] ?? []) : [];
            $scroll_id = is_array($body) ? (string) ($body['scroll_id'] ?? '') : '';

            if (empty($ids)) {
                $terminado = true;
                break;
            }

            if ($test_limit > 0) {
                $ids = array_slice($ids, 0, max(0, $test_limit - $total_listed));
            }

            foreach ($ids as $item_id) {
                // UPSERT (no INSERT IGNORE): un item que ya existía de una corrida
                // anterior debe volver a 'pending' para que la fase de detalle lo
                // revalide de nuevo. Si solo se ignorara por ya existir, nunca se
                // confirmaría su continuidad y finalizar() lo marcaría inactivo
                // por error, aunque siga activo en Mercado Libre.
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO `{$queue_table}` (item_id, status, attempts) VALUES (%s, 'pending', 0)
                     ON DUPLICATE KEY UPDATE status = 'pending', attempts = 0, http_status = NULL, detail = ''",
                    (string) $item_id
                ));
                $total_listed++;
            }

            if ($test_limit > 0 && $total_listed >= $test_limit) {
                $terminado = true;
                break;
            }

            if ($scroll_id === '') {
                $terminado = true;
                break;
            }
        }

        if ($terminado) {
            update_option(self::STATE_OPTION, array_merge($state, [
                'phase' => 'detail',
                'scroll_id' => '',
                'total_listed' => $total_listed,
            ]), false);
            return;
        }

        update_option(self::STATE_OPTION, array_merge($state, [
            'scroll_id' => $scroll_id,
            'total_listed' => $total_listed,
        ]), false);
    }

    private static function process_detail_batch(array $state): void {
        global $wpdb;
        $token = self::ml_access_token();
        if ($token === '') {
            return;
        }

        $queue_table = M2Base_Catalogo_ML_Schema::table_queue();

        $wpdb->query($wpdb->prepare(
            "UPDATE `{$queue_table}` SET status = 'error', detail = 'Máximo de intentos alcanzado.' WHERE status = 'pending' AND attempts >= %d",
            self::MAX_ATTEMPTS
        ));

        $pending = $wpdb->get_col($wpdb->prepare(
            "SELECT item_id FROM `{$queue_table}` WHERE status = 'pending' AND attempts < %d LIMIT %d",
            self::MAX_ATTEMPTS,
            self::MULTIGET_SIZE * self::DETAIL_CHUNKS_PER_TICK
        ));

        if (empty($pending)) {
            self::finalizar($state);
            return;
        }

        foreach (array_chunk($pending, self::MULTIGET_SIZE) as $chunk) {
            self::process_multiget_chunk($chunk, $token);
            usleep(300000);
        }
    }

    private static function process_multiget_chunk(array $item_ids, string $token): void {
        global $wpdb;
        $queue_table = M2Base_Catalogo_ML_Schema::table_queue();

        $url = self::API_URL . '/items?' . http_build_query([
            'ids' => implode(',', $item_ids),
            'attributes' => 'id,title,price,currency_id,available_quantity,sold_quantity,condition,category_id,attributes,pictures,permalink,status',
        ]);

        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            self::marcar_intento_fallido($item_ids, 'Error de red o multiget.');
            return;
        }

        $results = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($results)) {
            self::marcar_intento_fallido($item_ids, 'Respuesta de multiget inválida.');
            return;
        }

        $found_ids = [];
        foreach ($results as $entry) {
            $code = (int) ($entry['code'] ?? 0);
            $body = is_array($entry['body'] ?? null) ? $entry['body'] : [];
            $item_id = (string) ($body['id'] ?? '');
            if ($item_id === '') {
                continue;
            }
            $found_ids[] = $item_id;

            if ($code !== 200) {
                $wpdb->update($queue_table, [
                    'status' => 'error',
                    'http_status' => $code,
                    'detail' => 'Multiget devolvió error para este item.',
                    'processed_at' => current_time('mysql'),
                ], ['item_id' => $item_id]);
                continue;
            }

            self::guardar_item($body);

            $wpdb->update($queue_table, [
                'status' => 'ok',
                'http_status' => 200,
                'detail' => 'OK',
                'processed_at' => current_time('mysql'),
            ], ['item_id' => $item_id]);
        }

        $missing = array_diff($item_ids, $found_ids);
        if (!empty($missing)) {
            self::marcar_intento_fallido(array_values($missing), 'No vino en la respuesta de multiget.');
        }
    }

    private static function marcar_intento_fallido(array $item_ids, string $detail): void {
        global $wpdb;
        $queue_table = M2Base_Catalogo_ML_Schema::table_queue();
        $placeholders = implode(',', array_fill(0, count($item_ids), '%s'));
        $wpdb->query($wpdb->prepare(
            "UPDATE `{$queue_table}` SET attempts = attempts + 1, detail = %s WHERE item_id IN ({$placeholders})",
            array_merge([$detail], $item_ids)
        ));
    }

    private static function guardar_item(array $body): void {
        $brand = '';
        $part_number = '';
        foreach ((array) ($body['attributes'] ?? []) as $attr) {
            if (($attr['id'] ?? '') === 'BRAND') {
                $brand = (string) ($attr['value_name'] ?? '');
            } elseif (($attr['id'] ?? '') === 'PART_NUMBER') {
                $part_number = (string) ($attr['value_name'] ?? '');
            }
        }

        $title = (string) ($body['title'] ?? '');
        $vehiculo = M2Base_Catalogo_ML_Parser::parse_titulo($title);

        $pictures = is_array($body['pictures'] ?? null) ? $body['pictures'] : [];
        $thumbnail = '';
        if (!empty($pictures[0])) {
            $thumbnail = (string) ($pictures[0]['secure_url'] ?? ($pictures[0]['url'] ?? ''));
        }

        M2Base_Catalogo_ML_Repository::upsert_item([
            'item_id' => (string) $body['id'],
            'title' => $title,
            'price' => $body['price'] ?? 0,
            'currency_id' => $body['currency_id'] ?? '',
            'available_quantity' => $body['available_quantity'] ?? 0,
            'sold_quantity' => $body['sold_quantity'] ?? 0,
            'item_condition' => $body['condition'] ?? '',
            'category_id' => $body['category_id'] ?? '',
            'category_name' => self::resolve_category_name((string) ($body['category_id'] ?? '')),
            'brand' => $brand,
            'part_number' => $part_number,
            'vehicle_brand' => $vehiculo['vehicle_brand'],
            'vehicle_model' => $vehiculo['vehicle_model'],
            'vehicle_year_from' => $vehiculo['vehicle_year_from'],
            'vehicle_year_to' => $vehiculo['vehicle_year_to'],
            'vehicle_match_conf' => $vehiculo['vehicle_match_conf'],
            'thumbnail' => $thumbnail,
            'permalink' => $body['permalink'] ?? '',
            'status' => $body['status'] ?? 'active',
            'raw_attributes' => wp_json_encode($body['attributes'] ?? []),
        ]);
    }

    private static function resolve_category_name(string $category_id): string {
        if ($category_id === '') {
            return '';
        }

        $cache = get_option(self::CATEGORY_CACHE_OPTION, []);
        if (!is_array($cache)) {
            $cache = [];
        }
        if (isset($cache[$category_id])) {
            return $cache[$category_id];
        }

        $response = wp_remote_get(self::API_URL . '/categories/' . rawurlencode($category_id), ['timeout' => 15]);
        $name = $category_id;
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (is_array($body) && !empty($body['name'])) {
                $name = (string) $body['name'];
            }
        }

        $cache[$category_id] = $name;
        update_option(self::CATEGORY_CACHE_OPTION, $cache, false);
        return $name;
    }

    private static function finalizar(array $state): void {
        global $wpdb;
        $started_at_gmt = (string) ($state['started_at_gmt'] ?? current_time('mysql', true));
        $test_limit = (int) ($state['test_limit'] ?? 0);
        $total_listed = (int) ($state['total_listed'] ?? 0);

        // Salvaguarda: si esta corrida no llegó a listar nada (ej. un error de
        // red/token cortó la fase de listado) y no era una prueba a propósito
        // limitada, no hay forma de saber qué sigue activo de verdad — mejor
        // dejar el catálogo como estaba que marcarlo todo inactivo por error.
        if ($total_listed === 0 && $test_limit === 0) {
            update_option(self::STATE_OPTION, array_merge($state, ['phase' => 'done']), false);
            return;
        }

        // En una corrida de prueba (test_limit > 0) solo se revalidó una
        // muestra parcial del catálogo, así que tampoco se puede usar para
        // marcar inactivo lo que no apareció en esa muestra.
        if ($test_limit === 0) {
            M2Base_Catalogo_ML_Repository::marcar_inactivos_antes_de($started_at_gmt);
        }
        M2Base_Catalogo_ML_Repository::invalidar_cache_filtros();

        $queue_table = M2Base_Catalogo_ML_Schema::table_queue();
        $counts = $wpdb->get_row(
            "SELECT
                SUM(status = 'ok') AS ok_count,
                SUM(status = 'error') AS error_count,
                COUNT(*) AS total_count
            FROM `{$queue_table}`",
            ARRAY_A
        );

        $runs_table = M2Base_Catalogo_ML_Schema::table_runs();
        $wpdb->update($runs_table, [
            'phase' => 'done',
            'total_listed' => (int) ($counts['total_count'] ?? 0),
            'total_ok' => (int) ($counts['ok_count'] ?? 0),
            'total_error' => (int) ($counts['error_count'] ?? 0),
            'finished_at' => current_time('mysql'),
        ], ['id' => (int) ($state['run_id'] ?? 0)]);

        update_option(self::STATE_OPTION, array_merge($state, ['phase' => 'done']), false);
    }
}
