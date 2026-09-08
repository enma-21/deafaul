<?php
/**
 * Plugin Name: M2Base Repuestos
 * Description: Catálogo interno y buscador de repuestos de vehículos (marca, modelo, año, categoría). Pensado como base para integrar más adelante WooCommerce + Stripe / transferencia bancaria.
 * Version: 1.0.2
 * Author: M2Base
 * Text Domain: m2base-repuestos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'M2BASE_REPUESTOS_VERSION', '1.0.2' );
define( 'M2BASE_REPUESTOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'M2BASE_REPUESTOS_URL', plugin_dir_url( __FILE__ ) );

require_once M2BASE_REPUESTOS_PATH . 'includes/class-m2base-cpt.php';
require_once M2BASE_REPUESTOS_PATH . 'includes/class-m2base-metaboxes.php';
require_once M2BASE_REPUESTOS_PATH . 'includes/class-m2base-render.php';
require_once M2BASE_REPUESTOS_PATH . 'includes/class-m2base-search.php';

function m2base_repuestos_init() {
	M2Base_Repuestos_CPT::init();
	M2Base_Repuestos_Metaboxes::init();
	M2Base_Repuestos_Search::init();
}
add_action( 'plugins_loaded', 'm2base_repuestos_init' );

function m2base_repuestos_activate() {
	M2Base_Repuestos_CPT::register_post_type();
	M2Base_Repuestos_CPT::register_taxonomies();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'm2base_repuestos_activate' );

function m2base_repuestos_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'm2base_repuestos_deactivate' );
