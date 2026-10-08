<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Wab_Repository {

    public static function obtener_o_crear_conversacion(string $wa_id, string $nombre_perfil = ''): array {
        global $wpdb;
        $table = M2Wab_Schema::table_conversaciones();

        $fila = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE wa_id = %s", $wa_id),
            ARRAY_A
        );

        if ($fila) {
            if ($nombre_perfil !== '' && $fila['nombre_perfil'] !== $nombre_perfil) {
                $wpdb->update($table, ['nombre_perfil' => $nombre_perfil], ['wa_id' => $wa_id]);
                $fila['nombre_perfil'] = $nombre_perfil;
            }
            return $fila;
        }

        $wpdb->insert($table, [
            'wa_id' => $wa_id,
            'nombre_perfil' => $nombre_perfil,
            'contexto' => wp_json_encode([]),
            'last_message_at' => current_time('mysql', true),
        ]);

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE wa_id = %s", $wa_id),
            ARRAY_A
        );
    }

    public static function obtener_contexto(array $conversacion): array {
        $contexto = json_decode((string) ($conversacion['contexto'] ?? ''), true);
        return is_array($contexto) ? $contexto : [];
    }

    public static function guardar_contexto(string $wa_id, array $contexto): void {
        global $wpdb;
        $table = M2Wab_Schema::table_conversaciones();

        $wpdb->update(
            $table,
            [
                'contexto' => wp_json_encode($contexto),
                'last_message_at' => current_time('mysql', true),
            ],
            ['wa_id' => $wa_id]
        );
    }

    public static function ya_procesado(string $wa_message_id): bool {
        if ($wa_message_id === '') {
            return false;
        }

        global $wpdb;
        $table = M2Wab_Schema::table_mensajes();

        $existe = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM `{$table}` WHERE wa_message_id = %s LIMIT 1", $wa_message_id)
        );

        return $existe !== null;
    }

    public static function guardar_mensaje(string $wa_id, string $direccion, string $body, string $wa_message_id = '', string $tipo = 'text'): void {
        global $wpdb;
        $table = M2Wab_Schema::table_mensajes();

        $wpdb->insert($table, [
            'wa_id' => $wa_id,
            'wa_message_id' => $wa_message_id !== '' ? $wa_message_id : null,
            'direccion' => $direccion,
            'tipo' => $tipo,
            'body' => $body,
        ]);
    }

    public static function contar_conversaciones(): int {
        global $wpdb;
        $table = M2Wab_Schema::table_conversaciones();
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
    }

    public static function contar_mensajes_24h(): int {
        global $wpdb;
        $table = M2Wab_Schema::table_mensajes();
        $desde = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS);

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", $desde)
        );
    }
}
