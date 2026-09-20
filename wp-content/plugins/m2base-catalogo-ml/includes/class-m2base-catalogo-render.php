<?php

if (!defined('ABSPATH')) {
    exit;
}

final class M2Base_Catalogo_ML_Render {

    public static function precio_formateado(array $fila): string {
        $moneda = (string) ($fila['currency_id'] ?? '');
        $precio = (float) ($fila['price'] ?? 0);
        $simbolo = $moneda === 'USD' ? '$' : ($moneda !== '' ? $moneda . ' ' : '$');
        return $simbolo . number_format_i18n($precio, 2);
    }

    public static function compatibilidad_formateada(array $fila): string {
        $marca = (string) ($fila['vehicle_brand'] ?? '');
        $modelo = (string) ($fila['vehicle_model'] ?? '');
        $desde = $fila['vehicle_year_from'] ?? null;
        $hasta = $fila['vehicle_year_to'] ?? null;

        $texto = trim($marca . ' ' . $modelo);
        if ($desde || $hasta) {
            $anios = ($desde && $hasta && $desde !== $hasta) ? "{$desde}-{$hasta}" : (string) ($desde ?: $hasta);
            $texto .= " ({$anios})";
        }
        return trim($texto);
    }

    public static function tarjeta_html(array $fila): string {
        $condicion = ($fila['item_condition'] ?? '') === 'used' ? __('Usado', 'm2base-catalogo-ml') : __('Nuevo', 'm2base-catalogo-ml');
        $compat = self::compatibilidad_formateada($fila);

        ob_start();
        ?>
        <article class="m2mlc-card">
            <a class="m2mlc-card__enlace" href="<?php echo esc_url($fila['permalink'] ?? '#'); ?>" target="_blank" rel="noopener">
                <div class="m2mlc-card__imagen">
                    <?php if (!empty($fila['thumbnail'])) : ?>
                        <img src="<?php echo esc_url($fila['thumbnail']); ?>" alt="<?php echo esc_attr($fila['title'] ?? ''); ?>" loading="lazy">
                    <?php else : ?>
                        <div class="m2mlc-card__placeholder" aria-hidden="true">🔧</div>
                    <?php endif; ?>
                    <span class="m2mlc-badge"><?php echo esc_html($condicion); ?></span>
                </div>
                <div class="m2mlc-card__cuerpo">
                    <?php if (!empty($fila['category_name'])) : ?>
                        <span class="m2mlc-card__categoria"><?php echo esc_html($fila['category_name']); ?></span>
                    <?php endif; ?>
                    <h3 class="m2mlc-card__titulo"><?php echo esc_html($fila['title'] ?? ''); ?></h3>
                    <?php if ($compat) : ?>
                        <p class="m2mlc-card__compat"><?php echo esc_html($compat); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($fila['part_number'])) : ?>
                        <p class="m2mlc-card__parte">
                            <?php
                            printf(
                                /* translators: %s: número de parte */
                                esc_html__('N.º parte: %s', 'm2base-catalogo-ml'),
                                esc_html($fila['part_number'])
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                    <p class="m2mlc-card__precio"><?php echo esc_html(self::precio_formateado($fila)); ?></p>
                    <p class="m2mlc-card__stock">
                        <?php
                        printf(
                            /* translators: %d: unidades disponibles */
                            esc_html__('Stock: %d', 'm2base-catalogo-ml'),
                            (int) ($fila['available_quantity'] ?? 0)
                        );
                        ?>
                    </p>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }

    public static function resultados_html(array $filas, int $total, int $pagina = 1, int $por_pagina = 24): string {
        if (empty($filas)) {
            return '<p class="m2mlc-sin-resultados">' . esc_html__('No encontramos publicaciones que coincidan con tu búsqueda. Prueba con otros filtros.', 'm2base-catalogo-ml') . '</p>';
        }

        $html = '<p class="m2mlc-total">' . esc_html(sprintf(
            /* translators: %d: total de resultados */
            _n('%d resultado', '%d resultados', $total, 'm2base-catalogo-ml'),
            $total
        )) . '</p>';

        $html .= '<div class="m2mlc-grid" data-m2mlc-grid>';
        foreach ($filas as $fila) {
            $html .= self::tarjeta_html($fila);
        }
        $html .= '</div>';

        if ($pagina * $por_pagina < $total) {
            $html .= '<div class="m2mlc-cargar-mas-wrap" data-m2mlc-cargar-mas-wrap>';
            $html .= '<button type="button" class="m2mlc-buscador__boton" data-m2mlc-cargar-mas data-pagina="' . (int) ($pagina + 1) . '">';
            $html .= esc_html__('Cargar más', 'm2base-catalogo-ml');
            $html .= '</button></div>';
        }

        return $html;
    }
}
