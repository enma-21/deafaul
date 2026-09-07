<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class M2Base_Repuestos_Render {

	public static function obtener_datos( $post_id ) {
		$datos = array(
			'id'           => $post_id,
			'titulo'       => get_the_title( $post_id ),
			'permalink'    => get_permalink( $post_id ),
			'imagen'       => get_the_post_thumbnail_url( $post_id, 'medium' ),
			'excerpt'      => get_the_excerpt( $post_id ),
			'sku'          => get_post_meta( $post_id, '_m2base_sku', true ),
			'numero_parte' => get_post_meta( $post_id, '_m2base_numero_parte', true ),
			'precio'       => get_post_meta( $post_id, '_m2base_precio', true ),
			'anio_desde'   => get_post_meta( $post_id, '_m2base_anio_desde', true ),
			'anio_hasta'   => get_post_meta( $post_id, '_m2base_anio_hasta', true ),
			'stock'        => get_post_meta( $post_id, '_m2base_stock', true ),
			'condicion'    => get_post_meta( $post_id, '_m2base_condicion', true ),
			'categorias'   => wp_get_post_terms( $post_id, 'categoria_repuesto', array( 'fields' => 'names' ) ),
			'marcas'       => wp_get_post_terms( $post_id, 'marca_vehiculo', array( 'fields' => 'names' ) ),
			'modelos'      => wp_get_post_terms( $post_id, 'modelo_vehiculo', array( 'fields' => 'names' ) ),
		);

		return $datos;
	}

	public static function precio_formateado( $datos ) {
		if ( '' === $datos['precio'] || null === $datos['precio'] ) {
			return __( 'Consultar precio', 'm2base-repuestos' );
		}
		return '$' . number_format_i18n( (float) $datos['precio'], 2 );
	}

	public static function compatibilidad_formateada( $datos ) {
		$marca = ! empty( $datos['marcas'] ) ? implode( ', ', $datos['marcas'] ) : '';
		$anios = '';
		if ( $datos['anio_desde'] || $datos['anio_hasta'] ) {
			$anios = trim( $datos['anio_desde'] . ' - ' . $datos['anio_hasta'], ' -' );
		}
		return trim( $marca . ( $anios ? " ({$anios})" : '' ) );
	}

	public static function tarjeta_html( $post_id ) {
		$d = self::obtener_datos( $post_id );
		ob_start();
		?>
		<article class="m2base-card">
			<a class="m2base-card__enlace" href="<?php echo esc_url( $d['permalink'] ); ?>">
				<div class="m2base-card__imagen">
					<?php if ( $d['imagen'] ) : ?>
						<img src="<?php echo esc_url( $d['imagen'] ); ?>" alt="<?php echo esc_attr( $d['titulo'] ); ?>" loading="lazy" />
					<?php else : ?>
						<div class="m2base-card__placeholder" aria-hidden="true">🔧</div>
					<?php endif; ?>
					<?php if ( 'usado' === $d['condicion'] || 'reacondicionado' === $d['condicion'] ) : ?>
						<span class="m2base-badge"><?php echo esc_html( ucfirst( $d['condicion'] ) ); ?></span>
					<?php endif; ?>
				</div>
				<div class="m2base-card__cuerpo">
					<?php if ( ! empty( $d['categorias'] ) ) : ?>
						<span class="m2base-card__categoria"><?php echo esc_html( $d['categorias'][0] ); ?></span>
					<?php endif; ?>
					<h3 class="m2base-card__titulo"><?php echo esc_html( $d['titulo'] ); ?></h3>
					<?php $compat = self::compatibilidad_formateada( $d ); ?>
					<?php if ( $compat ) : ?>
						<p class="m2base-card__compat"><?php echo esc_html( $compat ); ?></p>
					<?php endif; ?>
					<?php if ( $d['numero_parte'] ) : ?>
						<p class="m2base-card__parte">
							<?php
							printf(
								/* translators: %s: número de parte OEM */
								esc_html__( 'N.º parte: %s', 'm2base-repuestos' ),
								esc_html( $d['numero_parte'] )
							);
							?>
						</p>
					<?php endif; ?>
					<p class="m2base-card__precio"><?php echo esc_html( self::precio_formateado( $d ) ); ?></p>
				</div>
			</a>
		</article>
		<?php
		return ob_get_clean();
	}

	public static function resultados_html( $query ) {
		if ( ! $query->have_posts() ) {
			return '<p class="m2base-sin-resultados">' . esc_html__( 'No encontramos repuestos que coincidan con tu búsqueda. Prueba con otros filtros o contáctanos directamente.', 'm2base-repuestos' ) . '</p>';
		}

		$html = '<div class="m2base-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= self::tarjeta_html( get_the_ID() );
		}
		wp_reset_postdata();
		$html .= '</div>';

		return $html;
	}
}
