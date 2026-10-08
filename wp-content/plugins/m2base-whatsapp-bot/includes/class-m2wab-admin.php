<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Wab_Admin {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'admin_menu']);
        add_action('admin_post_m2wab_save_settings', [self::class, 'save_settings']);
    }

    public static function admin_menu(): void {
        add_options_page(
            'Bot de WhatsApp',
            'Bot WhatsApp',
            'manage_options',
            'm2base-whatsapp-bot',
            [self::class, 'settings_page']
        );
    }

    public static function save_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.', 'Bot de WhatsApp', ['response' => 403]);
        }
        check_admin_referer('m2wab_save_settings');

        M2Wab_Settings::guardar([
            'verify_token' => isset($_POST['verify_token']) ? sanitize_text_field(wp_unslash($_POST['verify_token'])) : '',
            'phone_number_id' => isset($_POST['phone_number_id']) ? wp_unslash($_POST['phone_number_id']) : '',
            'waba_id' => isset($_POST['waba_id']) ? wp_unslash($_POST['waba_id']) : '',
            'access_token' => isset($_POST['access_token']) ? wp_unslash($_POST['access_token']) : '',
            'app_secret' => isset($_POST['app_secret']) ? wp_unslash($_POST['app_secret']) : '',
        ]);

        wp_safe_redirect(add_query_arg(['page' => 'm2base-whatsapp-bot', 'saved' => '1'], admin_url('options-general.php')));
        exit;
    }

    public static function settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = M2Wab_Settings::all();
        if ($settings['verify_token'] === '') {
            M2Wab_Settings::guardar(['verify_token' => wp_generate_password(32, false)]);
            $settings = M2Wab_Settings::all();
        }

        $tiene_access_token = M2Wab_Settings::access_token() !== '';
        $tiene_app_secret = M2Wab_Settings::app_secret() !== '';
        ?>
        <div class="wrap">
            <h1>Bot de WhatsApp</h1>
            <p>
                Recibe mensajes de WhatsApp Business (Meta Cloud API) y responde consultando el catálogo ya
                sincronizado por M2Base Catálogo Mercado Libre. El generador de respuesta es provisional
                (búsqueda directa por palabras clave) hasta definir el proveedor de IA que lo reemplazará.
            </p>

            <?php if (isset($_GET['saved'])) : ?>
                <div class="notice notice-success"><p>Ajustes guardados.</p></div>
            <?php endif; ?>

            <?php if (!class_exists('M2Base_Catalogo_ML_Repository')) : ?>
                <div class="notice notice-warning">
                    <p>El plugin M2Base Catálogo Mercado Libre no está activo — el bot no podrá consultar el catálogo hasta activarlo.</p>
                </div>
            <?php endif; ?>

            <table class="widefat striped" style="max-width:600px;margin-bottom:1em;">
                <tbody>
                    <tr><td>Conversaciones registradas</td><td><?php echo (int) M2Wab_Repository::contar_conversaciones(); ?></td></tr>
                    <tr><td>Mensajes últimas 24h</td><td><?php echo (int) M2Wab_Repository::contar_mensajes_24h(); ?></td></tr>
                </tbody>
            </table>

            <h2>Configuración del webhook en Meta</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">URL del webhook</th>
                    <td><code><?php echo esc_html(M2Wab_Webhook::webhook_url()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Verify token</th>
                    <td><code><?php echo esc_html($settings['verify_token']); ?></code></td>
                </tr>
            </table>
            <p>
                Pega esta URL y este token en Meta for Developers → tu app → WhatsApp → Configuration → Webhook,
                y suscríbete al campo <code>messages</code>.
            </p>

            <hr>
            <h2>Credenciales</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="m2wab_save_settings">
                <?php wp_nonce_field('m2wab_save_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="m2wab-phone">Phone Number ID</label></th>
                        <td><input type="text" id="m2wab-phone" name="phone_number_id" value="<?php echo esc_attr($settings['phone_number_id']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="m2wab-waba">WABA ID</label></th>
                        <td><input type="text" id="m2wab-waba" name="waba_id" value="<?php echo esc_attr($settings['waba_id']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="m2wab-token">Access Token</label></th>
                        <td>
                            <input type="password" id="m2wab-token" name="access_token" value="" class="regular-text" autocomplete="off"
                                placeholder="<?php echo $tiene_access_token ? esc_attr__('•••••••• (ya configurado, deja vacío para no cambiar)', 'm2base-whatsapp-bot') : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="m2wab-secret">App Secret</label></th>
                        <td>
                            <input type="password" id="m2wab-secret" name="app_secret" value="" class="regular-text" autocomplete="off"
                                placeholder="<?php echo $tiene_app_secret ? esc_attr__('•••••••• (ya configurado, deja vacío para no cambiar)', 'm2base-whatsapp-bot') : ''; ?>">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Guardar ajustes'); ?>
            </form>
        </div>
        <?php
    }
}
