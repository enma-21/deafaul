<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página de detalle propia para cada publicación del catálogo
 * (/repuesto-ml/{item_id}-{slug}/), con sección de recomendados y botón de
 * WhatsApp. Existe para que el tráfico de publicidad/búsqueda se quede en
 * nuestro sitio en vez de ir a la publicación real en Mercado Libre, donde
 * podrían verse ofertas de la competencia.
 *
 * Mismo patrón de rewrite+template_redirect que ya usan el webhook del bot
 * de WhatsApp y el callback de OAuth de Mercado Libre, en vez de un Custom
 * Post Type: el catálogo deliberadamente no usa post/postmeta de WordPress.
 */
final class M2Base_Catalogo_ML_Item {

    const QUERY_VAR = 'm2mlc_item';
    const REWRITE_VERSION = '1';
    const REWRITE_VERSION_OPTION = 'm2mlc_rewrite_version';
    const LIMITE_RELACIONADOS = 4;

    public static function init(): void {
        add_action('init', [self::class, 'register_routes']);
        add_filter('query_vars', [self::class, 'query_vars']);
        add_action('template_redirect', [self::class, 'handle_routes']);
    }

    public static function register_routes(): void {
        add_rewrite_rule(
            '^repuesto-ml/([A-Za-z0-9]+)(?:-[^/]*)?/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );

        // El plugin se despliega por FTP, no activando/desactivando desde
        // WordPress, así que esto detecta cuando hay que volver a avisarle
        // a WordPress de esta regla nueva (mismo problema ya resuelto para
        // el esquema de tablas en M2Base_Catalogo_ML_Schema::maybe_upgrade()).
        if (get_option(self::REWRITE_VERSION_OPTION) !== self::REWRITE_VERSION) {
            flush_rewrite_rules();
            update_option(self::REWRITE_VERSION_OPTION, self::REWRITE_VERSION, false);
        }
    }

    public static function query_vars(array $vars): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function url(string $item_id, string $titulo = ''): string {
        $slug = $titulo !== '' ? '-' . sanitize_title($titulo) : '';
        return home_url('/repuesto-ml/' . rawurlencode($item_id) . $slug . '/');
    }

    public static function handle_routes(): void {
        $item_id = get_query_var(self::QUERY_VAR);
        if (!$item_id) {
            return;
        }

        nocache_headers();

        $fila = M2Base_Catalogo_ML_Repository::obtener_por_item_id((string) $item_id);
        if ($fila === null) {
            self::render_404();
        } else {
            self::render_detalle($fila);
        }
        exit;
    }

    private static function render_404(): void {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part('404');
    }

    private static function render_detalle(array $fila): void {
        status_header(200);
        global $wp_query;
        $wp_query->is_404 = false;

        $titulo_pagina = (string) ($fila['title'] ?? '');
        $filtro_titulo = static function () use ($titulo_pagina) {
            return $titulo_pagina . ' — ' . get_bloginfo('name');
        };
        add_filter('pre_get_document_title', $filtro_titulo);

        $condicion = ($fila['item_condition'] ?? '') === 'used' ? __('Usado', 'm2base-catalogo-ml') : __('Nuevo', 'm2base-catalogo-ml');
        $compat = M2Base_Catalogo_ML_Render::compatibilidad_formateada($fila);
        $relacionados = M2Base_Catalogo_ML_Repository::relacionados($fila, self::LIMITE_RELACIONADOS);

        wp_enqueue_style('m2mlc-frontend', M2MLC_URL . 'assets/css/m2base-catalogo-frontend.css', [], M2MLC_VERSION);

        get_header();
        ?>
        <main id="contenido-principal" class="m2mlc-detalle-pagina">
            <div class="m2mlc-detalle">
                <nav class="m2mlc-detalle__migas" aria-label="<?php esc_attr_e('Ruta de navegación', 'm2base-catalogo-ml'); ?>">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Inicio', 'm2base-catalogo-ml'); ?></a>
                    <span aria-hidden="true">/</span>
                    <span><?php echo esc_html($titulo_pagina); ?></span>
                </nav>

                <div class="m2mlc-detalle__grid">
                    <div class="m2mlc-detalle__imagen">
                        <?php if (!empty($fila['thumbnail'])) : ?>
                            <img src="<?php echo esc_url($fila['thumbnail']); ?>" alt="<?php echo esc_attr($titulo_pagina); ?>">
                        <?php else : ?>
                            <div class="m2mlc-card__placeholder" aria-hidden="true">🔧</div>
                        <?php endif; ?>
                        <span class="m2mlc-badge"><?php echo esc_html($condicion); ?></span>
                    </div>

                    <div class="m2mlc-detalle__info">
                        <?php if (!empty($fila['category_name'])) : ?>
                            <span class="m2mlc-card__categoria"><?php echo esc_html($fila['category_name']); ?></span>
                        <?php endif; ?>

                        <h1><?php echo esc_html($titulo_pagina); ?></h1>

                        <p class="m2mlc-detalle__precio"><?php echo esc_html(M2Base_Catalogo_ML_Render::precio_formateado($fila)); ?></p>

                        <ul class="m2mlc-detalle__ficha">
                            <?php if (!empty($fila['part_number'])) : ?>
                                <li><strong><?php esc_html_e('N.º de parte:', 'm2base-catalogo-ml'); ?></strong> <?php echo esc_html($fila['part_number']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($fila['brand'])) : ?>
                                <li><strong><?php esc_html_e('Marca del repuesto:', 'm2base-catalogo-ml'); ?></strong> <?php echo esc_html($fila['brand']); ?></li>
                            <?php endif; ?>
                            <?php if ($compat) : ?>
                                <li><strong><?php esc_html_e('Compatible con:', 'm2base-catalogo-ml'); ?></strong> <?php echo esc_html($compat); ?></li>
                            <?php endif; ?>
                            <li>
                                <strong><?php esc_html_e('Disponibilidad:', 'm2base-catalogo-ml'); ?></strong>
                                <?php echo (int) ($fila['available_quantity'] ?? 0) > 0 ? esc_html__('En stock', 'm2base-catalogo-ml') : esc_html__('Consultar disponibilidad', 'm2base-catalogo-ml'); ?>
                            </li>
                        </ul>

                        <a class="m2mlc-card__whatsapp m2mlc-detalle__whatsapp" href="<?php echo esc_url(M2Base_Catalogo_ML_Render::whatsapp_url($fila)); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Consultar por WhatsApp', 'm2base-catalogo-ml'); ?>
                        </a>
                    </div>
                </div>

                <?php if (!empty($relacionados)) : ?>
                    <section class="m2mlc-relacionados">
                        <h2><?php esc_html_e('También te puede interesar', 'm2base-catalogo-ml'); ?></h2>
                        <div class="m2mlc-grid">
                            <?php foreach ($relacionados as $relacionado) : ?>
                                <?php echo M2Base_Catalogo_ML_Render::tarjeta_html($relacionado, true); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </main>
        <?php
        get_footer();

        remove_filter('pre_get_document_title', $filtro_titulo);
    }
}
