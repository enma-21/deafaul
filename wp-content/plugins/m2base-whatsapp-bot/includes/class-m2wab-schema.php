<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Wab_Schema {
    const DB_VERSION = '1.0.0';
    const VERSION_OPTION = 'm2wab_db_version';

    public static function table_conversaciones(): string {
        global $wpdb;
        return $wpdb->prefix . 'm2wab_conversaciones';
    }

    public static function table_mensajes(): string {
        global $wpdb;
        return $wpdb->prefix . 'm2wab_mensajes';
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
        $conversaciones = self::table_conversaciones();
        $mensajes = self::table_mensajes();

        dbDelta("CREATE TABLE {$conversaciones} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wa_id VARCHAR(30) NOT NULL,
            nombre_perfil VARCHAR(150) NOT NULL DEFAULT '',
            contexto LONGTEXT NULL,
            last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY wa_id (wa_id)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$mensajes} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wa_id VARCHAR(30) NOT NULL,
            wa_message_id VARCHAR(120) NULL DEFAULT NULL,
            direccion VARCHAR(10) NOT NULL DEFAULT 'in',
            tipo VARCHAR(20) NOT NULL DEFAULT 'text',
            body LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY wa_message_id (wa_message_id),
            KEY wa_id (wa_id)
        ) {$charset_collate};");

        update_option(self::VERSION_OPTION, self::DB_VERSION, false);
    }
}
