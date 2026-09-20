<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Base_Catalogo_ML_Schema {
    const DB_VERSION = '1.0.0';
    const VERSION_OPTION = 'm2mlc_db_version';

    public static function table_catalogo(): string {
        global $wpdb;
        return $wpdb->prefix . 'm2mlc_catalogo';
    }

    public static function table_queue(): string {
        global $wpdb;
        return $wpdb->prefix . 'm2mlc_sync_queue';
    }

    public static function table_runs(): string {
        global $wpdb;
        return $wpdb->prefix . 'm2mlc_sync_runs';
    }

    /**
     * El plugin se despliega por FTP, no activando/desactivando desde WordPress,
     * así que register_activation_hook solo no basta si se sube código nuevo con
     * un esquema distinto. Esto se llama en plugins_loaded para detectar el caso.
     */
    public static function maybe_upgrade(): void {
        if (get_option(self::VERSION_OPTION) !== self::DB_VERSION) {
            self::instalar();
        }
    }

    public static function instalar(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $catalogo = self::table_catalogo();
        $queue = self::table_queue();
        $runs = self::table_runs();

        dbDelta("CREATE TABLE {$catalogo} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency_id VARCHAR(5) NOT NULL DEFAULT '',
            available_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            sold_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            item_condition VARCHAR(20) NOT NULL DEFAULT '',
            category_id VARCHAR(20) NOT NULL DEFAULT '',
            category_name VARCHAR(150) NOT NULL DEFAULT '',
            brand VARCHAR(120) NOT NULL DEFAULT '',
            part_number VARCHAR(120) NOT NULL DEFAULT '',
            vehicle_brand VARCHAR(80) NOT NULL DEFAULT '',
            vehicle_model VARCHAR(120) NOT NULL DEFAULT '',
            vehicle_year_from SMALLINT UNSIGNED NULL,
            vehicle_year_to SMALLINT UNSIGNED NULL,
            vehicle_match_conf VARCHAR(10) NOT NULL DEFAULT '',
            thumbnail VARCHAR(500) NOT NULL DEFAULT '',
            permalink VARCHAR(500) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            raw_attributes LONGTEXT NULL,
            last_synced_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY item_id (item_id),
            KEY category_id (category_id),
            KEY vehicle_brand_model (vehicle_brand, vehicle_model),
            KEY price (price),
            KEY status (status),
            FULLTEXT KEY title_ft (title)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$queue} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            http_status SMALLINT UNSIGNED NULL,
            detail VARCHAR(255) NOT NULL DEFAULT '',
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY item_id (item_id),
            KEY status (status)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$runs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            phase VARCHAR(20) NOT NULL DEFAULT 'listing',
            scroll_id VARCHAR(255) NULL,
            total_listed INT UNSIGNED NOT NULL DEFAULT 0,
            total_ok INT UNSIGNED NOT NULL DEFAULT 0,
            total_error INT UNSIGNED NOT NULL DEFAULT 0,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL,
            started_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id)
        ) {$charset_collate};");

        update_option(self::VERSION_OPTION, self::DB_VERSION, false);
    }
}
