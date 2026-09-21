<?php
/**
 * Plugin Name: M2Base Bot de WhatsApp
 * Description: Recibe mensajes de WhatsApp Business (Meta Cloud API), responde consultando el catálogo sincronizado por M2Base Catálogo Mercado Libre. El generador de respuesta es provisional (búsqueda directa) hasta definir el proveedor de IA.
 * Version: 1.0.0
 * Author: M2 Base
 * Text Domain: m2base-whatsapp-bot
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('M2WAB_VERSION', '1.0.0');
define('M2WAB_PATH', plugin_dir_path(__FILE__));
define('M2WAB_URL', plugin_dir_url(__FILE__));

require_once M2WAB_PATH . 'includes/class-m2wab-schema.php';
require_once M2WAB_PATH . 'includes/class-m2wab-settings.php';
require_once M2WAB_PATH . 'includes/class-m2wab-repository.php';
require_once M2WAB_PATH . 'includes/class-m2wab-client.php';
require_once M2WAB_PATH . 'includes/class-m2wab-responder.php';
require_once M2WAB_PATH . 'includes/class-m2wab-webhook.php';
require_once M2WAB_PATH . 'includes/class-m2wab-admin.php';

function m2base_whatsapp_bot_init(): void {
    M2Wab_Schema::maybe_upgrade();
    M2Wab_Webhook::init();
    M2Wab_Admin::init();
}
add_action('plugins_loaded', 'm2base_whatsapp_bot_init');

function m2base_whatsapp_bot_activate(): void {
    M2Wab_Schema::instalar();
    M2Wab_Webhook::register_routes();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'm2base_whatsapp_bot_activate');

function m2base_whatsapp_bot_deactivate(): void {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'm2base_whatsapp_bot_deactivate');
