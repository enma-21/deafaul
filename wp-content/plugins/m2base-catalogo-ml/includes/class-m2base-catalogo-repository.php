<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Base_Catalogo_ML_Repository {

    private static function fulltext_disponible(): bool {
        global $wpdb;
        $cached = get_transient('m2mlc_fulltext_ok');
        if ($cached !== false) {
            return $cached === '1';
        }

        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        $index = $wpdb->get_row("SHOW INDEX FROM `{$table}` WHERE Key_name = 'title_ft'");
        $ok = $index !== null;
        set_transient('m2mlc_fulltext_ok', $ok ? '1' : '0', HOUR_IN_SECONDS);
        return $ok;
    }

    public static function upsert_item(array $item): void {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_catalogo();

        $data = [
            'item_id' => (string) $item['item_id'],
            'title' => (string) ($item['title'] ?? ''),
            'price' => (float) ($item['price'] ?? 0),
            'currency_id' => (string) ($item['currency_id'] ?? ''),
            'available_quantity' => (int) ($item['available_quantity'] ?? 0),
            'sold_quantity' => (int) ($item['sold_quantity'] ?? 0),
            'item_condition' => (string) ($item['item_condition'] ?? ''),
            'category_id' => (string) ($item['category_id'] ?? ''),
            'category_name' => (string) ($item['category_name'] ?? ''),
            'brand' => (string) ($item['brand'] ?? ''),
            'part_number' => (string) ($item['part_number'] ?? ''),
            'vehicle_brand' => (string) ($item['vehicle_brand'] ?? ''),
            'vehicle_model' => (string) ($item['vehicle_model'] ?? ''),
            'vehicle_year_from' => $item['vehicle_year_from'] ?? null,
            'vehicle_year_to' => $item['vehicle_year_to'] ?? null,
            'vehicle_match_conf' => (string) ($item['vehicle_match_conf'] ?? ''),
            'thumbnail' => (string) ($item['thumbnail'] ?? ''),
            'permalink' => (string) ($item['permalink'] ?? ''),
            'status' => (string) ($item['status'] ?? 'active'),
            'raw_attributes' => (string) ($item['raw_attributes'] ?? ''),
            'last_synced_at' => current_time('mysql', true),
        ];

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM `{$table}` WHERE item_id = %s",
            $data['item_id']
        ));

        if ($existing_id) {
            $wpdb->update($table, $data, ['id' => (int) $existing_id]);
        } else {
            $wpdb->insert($table, $data);
        }
    }

    public static function marcar_inactivos_antes_de(string $started_at_gmt): int {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE `{$table}` SET status = 'inactive' WHERE last_synced_at < %s AND status != 'inactive'",
            $started_at_gmt
        ));
        return $result === false ? 0 : (int) $result;
    }

    public static function buscar(array $filtros): array {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_catalogo();

        [$where, $params] = self::construir_where($filtros);

        $orden = 'title ASC';
        if (!empty($filtros['texto']) && self::fulltext_disponible()) {
            $orden = 'MATCH(title) AGAINST (%s IN NATURAL LANGUAGE MODE) DESC';
            $params[] = (string) $filtros['texto'];
        }

        $limit = max(1, min(60, (int) ($filtros['por_pagina'] ?? 24)));
        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $offset = ($pagina - 1) * $limit;

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT * FROM `{$table}` WHERE {$where} ORDER BY {$orden} LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [];
    }

    public static function contar(array $filtros): int {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        [$where, $params] = self::construir_where($filtros);
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";

        if (empty($params)) {
            return (int) $wpdb->get_var($sql);
        }
        return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /** @return array{0:string,1:array} */
    private static function construir_where(array $filtros): array {
        global $wpdb;
        $conditions = ["status = 'active'", 'available_quantity > 0'];
        $params = [];

        if (!empty($filtros['texto'])) {
            $texto = (string) $filtros['texto'];
            if (self::fulltext_disponible()) {
                $conditions[] = '(MATCH(title) AGAINST (%s IN NATURAL LANGUAGE MODE) OR part_number LIKE %s)';
                $params[] = $texto;
                $params[] = '%' . $wpdb->esc_like($texto) . '%';
            } else {
                $like = '%' . $wpdb->esc_like($texto) . '%';
                $conditions[] = '(title LIKE %s OR part_number LIKE %s)';
                $params[] = $like;
                $params[] = $like;
            }
        }

        if (!empty($filtros['vehicle_brand'])) {
            $conditions[] = 'vehicle_brand = %s';
            $params[] = (string) $filtros['vehicle_brand'];
        }

        if (!empty($filtros['vehicle_model'])) {
            $conditions[] = 'vehicle_model = %s';
            $params[] = (string) $filtros['vehicle_model'];
        }

        if (!empty($filtros['category_id'])) {
            $conditions[] = 'category_id = %s';
            $params[] = (string) $filtros['category_id'];
        }

        if (!empty($filtros['precio_min'])) {
            $conditions[] = 'price >= %f';
            $params[] = (float) $filtros['precio_min'];
        }

        if (!empty($filtros['precio_max'])) {
            $conditions[] = 'price <= %f';
            $params[] = (float) $filtros['precio_max'];
        }

        if (!empty($filtros['anio_desde']) || !empty($filtros['anio_hasta'])) {
            $anio = (int) ($filtros['anio_desde'] ?: $filtros['anio_hasta']);
            $conditions[] = '(vehicle_year_from IS NULL OR vehicle_year_from <= %d)';
            $conditions[] = '(vehicle_year_to IS NULL OR vehicle_year_to >= %d)';
            $params[] = $anio;
            $params[] = $anio;
        }

        return [implode(' AND ', $conditions), $params];
    }

    public static function marcas_disponibles(): array {
        global $wpdb;
        $cached = get_transient('m2mlc_marcas');
        if ($cached !== false) {
            return $cached;
        }

        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        $marcas = $wpdb->get_col(
            "SELECT DISTINCT vehicle_brand FROM `{$table}` WHERE vehicle_brand != '' AND status = 'active' ORDER BY vehicle_brand ASC"
        );
        set_transient('m2mlc_marcas', $marcas, HOUR_IN_SECONDS);
        return $marcas;
    }

    public static function modelos_por_marca(string $marca): array {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT vehicle_model FROM `{$table}` WHERE vehicle_brand = %s AND vehicle_model != '' AND status = 'active' ORDER BY vehicle_model ASC",
            $marca
        ));
    }

    public static function categorias_disponibles(): array {
        global $wpdb;
        $cached = get_transient('m2mlc_categorias');
        if ($cached !== false) {
            return $cached;
        }

        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        $categorias = $wpdb->get_results(
            "SELECT DISTINCT category_id, category_name FROM `{$table}` WHERE category_id != '' AND status = 'active' ORDER BY category_name ASC",
            ARRAY_A
        );
        $categorias = $categorias ?: [];
        set_transient('m2mlc_categorias', $categorias, HOUR_IN_SECONDS);
        return $categorias;
    }

    public static function invalidar_cache_filtros(): void {
        delete_transient('m2mlc_marcas');
        delete_transient('m2mlc_categorias');
        delete_transient('m2mlc_fulltext_ok');
    }

    public static function contar_activos(): int {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_catalogo();
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE status = 'active'");
    }
}
