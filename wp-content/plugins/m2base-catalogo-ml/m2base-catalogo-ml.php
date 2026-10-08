<?php
/**
 * Plugin Name: M2Base Catálogo Mercado Libre
 * Description: Sincroniza el catálogo completo de Mercado Libre a una base de datos MySQL propia. Incluye un buscador interno (wp-admin), un buscador público para el sitio web (shortcode [m2base_catalogo_ml]) con botones de WhatsApp, y una página de detalle propia por repuesto con recomendaciones relacionadas.
 * Version: 1.3.0
 * Author: M2 Base
 * Text Domain: m2base-catalogo-ml
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('M2MLC_VERSION', '1.3.0');
define('M2MLC_PATH', plugin_dir_path(__FILE__));
define('M2MLC_URL', plugin_dir_url(__FILE__));

require_once M2MLC_PATH . 'includes/class-m2base-catalogo-schema.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-parser.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-repository.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-sync.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-render.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-admin.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-search.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-item.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-publico.php';

const M2MLC_CRON_HOOK = 'm2mlc_cron_tick';
const M2MLC_CRON_INTERVALO = 'm2mlc_diez_minutos';

function m2base_catalogo_ml_cron_schedules(array $horarios): array {
    $horarios[M2MLC_CRON_INTERVALO] = [
        'interval' => 10 * MINUTE_IN_SECONDS,
        'display' => __('Cada 10 minutos (Catálogo Mercado Libre)', 'm2base-catalogo-ml'),
    ];
    return $horarios;
}
add_filter('cron_schedules', 'm2base_catalogo_ml_cron_schedules');

function m2base_catalogo_ml_init(): void {
    M2Base_Catalogo_ML_Schema::maybe_upgrade();
    M2Base_Catalogo_ML_Admin::init();
    M2Base_Catalogo_ML_Search::init();
    M2Base_Catalogo_ML_Item::init();
    M2Base_Catalogo_ML_Publico::init();

    add_action(M2MLC_CRON_HOOK, ['M2Base_Catalogo_ML_Sync', 'cron_tick']);

    // El plugin se despliega por FTP/editor, no activando/desactivando desde
    // WordPress (ver M2Base_Catalogo_ML_Item::register_routes para el mismo
    // problema con las reglas de reescritura), así que el cron se programa
    // aquí en cada carga en vez de depender solo del hook de activación.
    if (!wp_next_scheduled(M2MLC_CRON_HOOK)) {
        wp_schedule_event(time(), M2MLC_CRON_INTERVALO, M2MLC_CRON_HOOK);
    }
}
add_action('plugins_loaded', 'm2base_catalogo_ml_init');

function m2base_catalogo_ml_activate(): void {
    M2Base_Catalogo_ML_Schema::instalar();
}
register_activation_hook(__FILE__, 'm2base_catalogo_ml_activate');

function m2base_catalogo_ml_deactivate(): void {
    wp_clear_scheduled_hook(M2MLC_CRON_HOOK);
}
register_deactivation_hook(__FILE__, 'm2base_catalogo_ml_deactivate');
