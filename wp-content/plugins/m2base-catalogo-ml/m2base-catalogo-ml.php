<?php
/**
 * Plugin Name: M2Base Catálogo Mercado Libre
 * Description: Sincroniza el catálogo completo de Mercado Libre a una base de datos MySQL propia y ofrece un buscador/filtros interno (solo wp-admin). Base de datos previa al futuro bot de WhatsApp con IA.
 * Version: 1.0.0
 * Author: M2 Base
 * Text Domain: m2base-catalogo-ml
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('M2MLC_VERSION', '1.0.0');
define('M2MLC_PATH', plugin_dir_path(__FILE__));
define('M2MLC_URL', plugin_dir_url(__FILE__));

require_once M2MLC_PATH . 'includes/class-m2base-catalogo-schema.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-parser.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-repository.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-sync.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-render.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-admin.php';
require_once M2MLC_PATH . 'includes/class-m2base-catalogo-search.php';

function m2base_catalogo_ml_init(): void {
    M2Base_Catalogo_ML_Schema::maybe_upgrade();
    M2Base_Catalogo_ML_Admin::init();
    M2Base_Catalogo_ML_Search::init();
}
add_action('plugins_loaded', 'm2base_catalogo_ml_init');

function m2base_catalogo_ml_activate(): void {
    M2Base_Catalogo_ML_Schema::instalar();
}
register_activation_hook(__FILE__, 'm2base_catalogo_ml_activate');
