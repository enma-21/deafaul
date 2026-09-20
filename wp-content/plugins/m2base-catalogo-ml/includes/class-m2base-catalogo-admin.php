<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Base_Catalogo_ML_Admin {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'admin_menu']);
        add_action('admin_post_m2mlc_sync_start', [self::class, 'sync_start']);
        add_action('admin_post_m2mlc_sync_reset', [self::class, 'sync_reset']);
    }

    public static function admin_menu(): void {
        add_options_page(
            'Catálogo Mercado Libre',
            'Catálogo ML',
            'manage_options',
            'm2base-catalogo-ml',
            [self::class, 'settings_page']
        );
    }

    private static function redirect_settings(array $args): void {
        wp_safe_redirect(add_query_arg(array_merge(['page' => 'm2base-catalogo-ml'], $args), admin_url('options-general.php')));
        exit;
    }

    public static function sync_start(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Catálogo Mercado Libre', ['response' => 403]);
        }
        check_admin_referer('m2mlc_sync_start');

        $test_limit = !empty($_POST['test_limit']) ? 20 : 0;
        M2Base_Catalogo_ML_Sync::iniciar($test_limit);
        self::redirect_settings(['sync_started' => '1']);
    }

    public static function sync_reset(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Catálogo Mercado Libre', ['response' => 403]);
        }
        check_admin_referer('m2mlc_sync_reset');
        M2Base_Catalogo_ML_Sync::reiniciar();
        self::redirect_settings(['sync_reset' => '1']);
    }

    private static function contar_cola(): array {
        global $wpdb;
        $table = M2Base_Catalogo_ML_Schema::table_queue();
        $row = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total_count,
                SUM(status = 'pending') AS pending_count,
                SUM(status = 'ok') AS ok_count,
                SUM(status = 'error') AS error_count
            FROM `{$table}`",
            ARRAY_A
        );

        return [
            'total' => (int) ($row['total_count'] ?? 0),
            'pending' => (int) ($row['pending_count'] ?? 0),
            'ok' => (int) ($row['ok_count'] ?? 0),
            'error' => (int) ($row['error_count'] ?? 0),
        ];
    }

    public static function settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $conectado = class_exists('M2_Mercado_Libre_Integration') && M2_Mercado_Libre_Integration::access_token() !== '';
        $activo = M2Base_Catalogo_ML_Sync::esta_activo();

        if ($activo) {
            M2Base_Catalogo_ML_Sync::process_batch();
            $activo = M2Base_Catalogo_ML_Sync::esta_activo();
        }

        $counts = self::contar_cola();
        $catalogo_total = M2Base_Catalogo_ML_Repository::contar_activos();
        ?>
        <div class="wrap">
            <h1>Catálogo Mercado Libre</h1>
            <p>
                Sincroniza el catálogo completo de Mercado Libre a una base de datos propia para alimentar el
                buscador del sitio (shortcode <code>[m2base_catalogo_ml]</code>) y, más adelante, al bot de WhatsApp.
            </p>

            <?php if (isset($_GET['sync_started'])) : ?>
                <div class="notice notice-success"><p>Sincronización iniciada.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['sync_reset'])) : ?>
                <div class="notice notice-success"><p>Sincronización reiniciada.</p></div>
            <?php endif; ?>

            <?php if (!$conectado) : ?>
                <div class="notice notice-warning">
                    <p>Conecta primero la cuenta de Mercado Libre en <a href="<?php echo esc_url(admin_url('options-general.php?page=m2-mercadolibre')); ?>">Ajustes → Mercado Libre</a>.</p>
                </div>
            <?php endif; ?>

            <table class="widefat striped" style="max-width:500px;margin-bottom:1em;">
                <tbody>
                    <tr><td>Publicaciones en el catálogo (activas)</td><td><?php echo (int) $catalogo_total; ?></td></tr>
                    <tr><td>Cola de sincronización: total</td><td><?php echo (int) $counts['total']; ?></td></tr>
                    <tr><td>Cola: pendientes</td><td><?php echo (int) $counts['pending']; ?></td></tr>
                    <tr><td>Cola: OK</td><td><?php echo (int) $counts['ok']; ?></td></tr>
                    <tr><td>Cola: con error</td><td><?php echo (int) $counts['error']; ?></td></tr>
                </tbody>
            </table>

            <?php if (!$activo) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:1em;">
                    <input type="hidden" name="action" value="m2mlc_sync_start">
                    <?php wp_nonce_field('m2mlc_sync_start'); ?>
                    <p>
                        <label>
                            <input type="checkbox" name="test_limit" value="1">
                            Prueba: sincronizar solo 20 publicaciones (recomendado la primera vez).
                        </label>
                    </p>
                    <?php submit_button('Iniciar sincronización', 'primary', 'submit', false, $conectado ? [] : ['disabled' => 'disabled']); ?>
                </form>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;" onsubmit="return confirm('¿Seguro? Esto borra el progreso de la sincronización en curso (no borra el catálogo ya sincronizado).');">
                    <input type="hidden" name="action" value="m2mlc_sync_reset">
                    <?php wp_nonce_field('m2mlc_sync_reset'); ?>
                    <?php submit_button('Detener / reiniciar sincronización', 'delete', 'submit', false); ?>
                </form>
                <p><em>Sincronizando… esta página se recargará sola cada pocos segundos hasta terminar. No la cierres.</em></p>
                <script>setTimeout(function () { window.location.reload(); }, 4000);</script>
            <?php endif; ?>

            <hr>
            <h2>Buscador interno</h2>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=' . M2Base_Catalogo_ML_Search::PAGE_SLUG)); ?>">Buscador del catálogo</a>
                — herramienta solo para el admin de WordPress, no está publicada en el sitio.
            </p>
        </div>
        <?php
    }
}
