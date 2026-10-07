<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Versión pública del buscador del catálogo (shortcode), para usarlo como
 * embudo de ventas en el sitio web: el visitante busca/filtra sin iniciar
 * sesión y cada resultado tiene un botón directo a WhatsApp.
 *
 * Reutiliza intencionalmente los mismos nombres de acción AJAX que el
 * buscador admin-only (M2Base_Catalogo_ML_Search) para poder reusar el mismo
 * JS/CSS sin tocarlos: WordPress despacha wp_ajax_{action} a usuarios
 * logueados (sigue cayendo en el handler admin-gated, que los deja pasar
 * igual) y wp_ajax_nopriv_{action} a visitantes anónimos (cae aquí).
 */
final class M2Base_Catalogo_ML_Publico {

    const NONCE_ACTION = 'm2mlc_buscador_nonce';
    const SHORTCODE = 'm2base_catalogo_ml';
    const POR_PAGINA = 24;

    public static function init(): void {
        add_shortcode(self::SHORTCODE, [self::class, 'shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'registrar_assets']);

        add_action('wp_ajax_nopriv_m2mlc_buscar', [self::class, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_m2mlc_modelos_por_marca', [self::class, 'ajax_modelos_por_marca']);
    }

    public static function registrar_assets(): void {
        wp_register_style('m2mlc-frontend', M2MLC_URL . 'assets/css/m2base-catalogo-frontend.css', [], M2MLC_VERSION);
        wp_register_script('m2mlc-search', M2MLC_URL . 'assets/js/m2base-catalogo-search.js', [], M2MLC_VERSION, true);
    }

    public static function numero_whatsapp(): string {
        return (string) get_theme_mod('m2base_whatsapp_numero', '584245578110');
    }

    public static function shortcode($atts): string {
        $atts = shortcode_atts(
            ['titulo' => __('Encuentra el repuesto exacto para tu vehículo', 'm2base-catalogo-ml')],
            $atts,
            self::SHORTCODE
        );

        wp_localize_script(
            'm2mlc-search',
            'M2MLCatalogo',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'i18n' => [
                    'buscando' => __('Buscando…', 'm2base-catalogo-ml'),
                    'error' => __('Ocurrió un error al buscar. Intenta nuevamente.', 'm2base-catalogo-ml'),
                ],
            ]
        );
        wp_enqueue_style('m2mlc-frontend');
        wp_enqueue_script('m2mlc-search');

        $marcas = M2Base_Catalogo_ML_Repository::marcas_disponibles();
        $categorias = M2Base_Catalogo_ML_Repository::categorias_disponibles();

        ob_start();
        ?>
        <div class="m2mlc-buscador" data-m2mlc-buscador>
            <?php if (!empty($atts['titulo'])) : ?>
                <h2 class="m2mlc-buscador__titulo"><?php echo esc_html($atts['titulo']); ?></h2>
            <?php endif; ?>
            <form class="m2mlc-buscador__form" data-m2mlc-form>
                <div class="m2mlc-buscador__campo m2mlc-buscador__campo--texto">
                    <label for="m2mlc-texto"><?php esc_html_e('Buscar por nombre o número de parte', 'm2base-catalogo-ml'); ?></label>
                    <input type="text" id="m2mlc-texto" name="texto" placeholder="<?php esc_attr_e('Ej: pastillas de freno, filtro de aceite…', 'm2base-catalogo-ml'); ?>">
                </div>
                <div class="m2mlc-buscador__campo">
                    <label for="m2mlc-marca"><?php esc_html_e('Marca', 'm2base-catalogo-ml'); ?></label>
                    <select id="m2mlc-marca" name="vehicle_brand" data-m2mlc-marca>
                        <option value=""><?php esc_html_e('Todas', 'm2base-catalogo-ml'); ?></option>
                        <?php foreach ($marcas as $marca) : ?>
                            <option value="<?php echo esc_attr($marca); ?>"><?php echo esc_html($marca); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="m2mlc-buscador__campo">
                    <label for="m2mlc-modelo"><?php esc_html_e('Modelo', 'm2base-catalogo-ml'); ?></label>
                    <select id="m2mlc-modelo" name="vehicle_model" data-m2mlc-modelo disabled>
                        <option value=""><?php esc_html_e('Todos', 'm2base-catalogo-ml'); ?></option>
                    </select>
                </div>
                <div class="m2mlc-buscador__campo">
                    <label for="m2mlc-categoria"><?php esc_html_e('Categoría', 'm2base-catalogo-ml'); ?></label>
                    <select id="m2mlc-categoria" name="category_id">
                        <option value=""><?php esc_html_e('Todas', 'm2base-catalogo-ml'); ?></option>
                        <?php foreach ($categorias as $categoria) : ?>
                            <option value="<?php echo esc_attr($categoria['category_id']); ?>"><?php echo esc_html($categoria['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="m2mlc-buscador__campo m2mlc-buscador__campo--rango">
                    <label><?php esc_html_e('Año', 'm2base-catalogo-ml'); ?></label>
                    <input type="number" name="anio_desde" placeholder="<?php esc_attr_e('Desde', 'm2base-catalogo-ml'); ?>" min="1950" max="2100">
                    <input type="number" name="anio_hasta" placeholder="<?php esc_attr_e('Hasta', 'm2base-catalogo-ml'); ?>" min="1950" max="2100">
                </div>
                <div class="m2mlc-buscador__campo m2mlc-buscador__campo--rango">
                    <label><?php esc_html_e('Precio', 'm2base-catalogo-ml'); ?></label>
                    <input type="number" name="precio_min" placeholder="<?php esc_attr_e('Mín', 'm2base-catalogo-ml'); ?>" min="0" step="0.01">
                    <input type="number" name="precio_max" placeholder="<?php esc_attr_e('Máx', 'm2base-catalogo-ml'); ?>" min="0" step="0.01">
                </div>
                <button type="submit" class="m2mlc-buscador__boton">
                    <span data-m2mlc-boton-texto><?php esc_html_e('Buscar', 'm2base-catalogo-ml'); ?></span>
                </button>
            </form>
            <div class="m2mlc-buscador__resultados" data-m2mlc-resultados></div>
        </div>
        <?php echo self::boton_flotante_html(); ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Un solo botón flotante por carga de página, aunque el shortcode se
     * use más de una vez (ej. si en el futuro se agrega a varias páginas).
     */
    private static function boton_flotante_html(): string {
        static $ya_impreso = false;
        if ($ya_impreso) {
            return '';
        }
        $ya_impreso = true;

        $mensaje = __('Hola, quisiera más información sobre sus repuestos.', 'm2base-catalogo-ml');
        $url = 'https://wa.me/' . rawurlencode(self::numero_whatsapp()) . '?text=' . rawurlencode($mensaje);

        return '<a class="m2mlc-whatsapp-flotante" href="' . esc_url($url) . '" target="_blank" rel="noopener" aria-label="'
            . esc_attr__('Chatea con nosotros por WhatsApp', 'm2base-catalogo-ml') . '"><span>'
            . esc_html__('Chatea con nosotros', 'm2base-catalogo-ml') . '</span></a>';
    }

    private static function verificar_nonce_publico(): void {
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Sesión de búsqueda inválida, recarga la página.', 'm2base-catalogo-ml')], 403);
        }
    }

    public static function ajax_buscar(): void {
        self::verificar_nonce_publico();

        $filtros = [
            'texto' => isset($_POST['texto']) ? sanitize_text_field(wp_unslash($_POST['texto'])) : '',
            'vehicle_brand' => isset($_POST['vehicle_brand']) ? sanitize_text_field(wp_unslash($_POST['vehicle_brand'])) : '',
            'vehicle_model' => isset($_POST['vehicle_model']) ? sanitize_text_field(wp_unslash($_POST['vehicle_model'])) : '',
            'category_id' => isset($_POST['category_id']) ? sanitize_text_field(wp_unslash($_POST['category_id'])) : '',
            'anio_desde' => isset($_POST['anio_desde']) ? absint($_POST['anio_desde']) : 0,
            'anio_hasta' => isset($_POST['anio_hasta']) ? absint($_POST['anio_hasta']) : 0,
            'precio_min' => isset($_POST['precio_min']) ? (float) $_POST['precio_min'] : 0,
            'precio_max' => isset($_POST['precio_max']) ? (float) $_POST['precio_max'] : 0,
            'pagina' => isset($_POST['pagina']) ? max(1, absint($_POST['pagina'])) : 1,
            'por_pagina' => self::POR_PAGINA,
        ];

        $filas = M2Base_Catalogo_ML_Repository::buscar($filtros);
        $total = M2Base_Catalogo_ML_Repository::contar($filtros);

        wp_send_json_success([
            'html' => M2Base_Catalogo_ML_Render::resultados_html($filas, $total, $filtros['pagina'], self::POR_PAGINA, true),
            'total' => $total,
        ]);
    }

    public static function ajax_modelos_por_marca(): void {
        self::verificar_nonce_publico();

        $marca = isset($_POST['marca']) ? sanitize_text_field(wp_unslash($_POST['marca'])) : '';
        if ($marca === '') {
            wp_send_json_success(['modelos' => []]);
        }

        $modelos = M2Base_Catalogo_ML_Repository::modelos_por_marca($marca);
        wp_send_json_success([
            'modelos' => array_map(
                static function (string $modelo): array {
                    return ['id' => $modelo, 'nombre' => $modelo];
                },
                $modelos
            ),
        ]);
    }
}
