<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class M2Base_Repuestos_Metaboxes {

	const NONCE_ACTION = 'm2base_guardar_ficha_repuesto';
	const NONCE_NAME   = 'm2base_ficha_repuesto_nonce';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metabox' ) );
		add_action( 'save_post_repuesto', array( __CLASS__, 'guardar' ) );
	}

	public static function registrar_metabox() {
		add_meta_box(
			'm2base_ficha_repuesto',
			__( 'Ficha técnica y compatibilidad', 'm2base-repuestos' ),
			array( __CLASS__, 'render' ),
			'repuesto',
			'normal',
			'high'
		);
	}

	public static function campos() {
		return array(
			'sku'             => __( 'SKU / Código interno', 'm2base-repuestos' ),
			'numero_parte'    => __( 'Número de parte (OEM)', 'm2base-repuestos' ),
			'precio'          => __( 'Precio (referencial)', 'm2base-repuestos' ),
			'anio_desde'      => __( 'Año desde', 'm2base-repuestos' ),
			'anio_hasta'      => __( 'Año hasta', 'm2base-repuestos' ),
			'stock'           => __( 'Stock disponible', 'm2base-repuestos' ),
		);
	}

	public static function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$valores    = array();
		foreach ( array_keys( self::campos() ) as $clave ) {
			$valores[ $clave ] = get_post_meta( $post->ID, '_m2base_' . $clave, true );
		}
		$condicion = get_post_meta( $post->ID, '_m2base_condicion', true );
		if ( ! $condicion ) {
			$condicion = 'nuevo';
		}
		?>
		<style>
			.m2base-campos { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
			.m2base-campos label { display: block; font-weight: 600; margin-bottom: 4px; }
			.m2base-campos input, .m2base-campos select { width: 100%; }
		</style>
		<div class="m2base-campos">
			<?php foreach ( self::campos() as $clave => $etiqueta ) : ?>
				<div>
					<label for="m2base_<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $etiqueta ); ?></label>
					<input
						type="<?php echo in_array( $clave, array( 'precio', 'anio_desde', 'anio_hasta', 'stock' ), true ) ? 'number' : 'text'; ?>"
						id="m2base_<?php echo esc_attr( $clave ); ?>"
						name="m2base_<?php echo esc_attr( $clave ); ?>"
						value="<?php echo esc_attr( $valores[ $clave ] ); ?>"
						step="<?php echo 'precio' === $clave ? '0.01' : '1'; ?>"
					/>
				</div>
			<?php endforeach; ?>
			<div>
				<label for="m2base_condicion"><?php esc_html_e( 'Condición', 'm2base-repuestos' ); ?></label>
				<select id="m2base_condicion" name="m2base_condicion">
					<option value="nuevo" <?php selected( $condicion, 'nuevo' ); ?>><?php esc_html_e( 'Nuevo', 'm2base-repuestos' ); ?></option>
					<option value="usado" <?php selected( $condicion, 'usado' ); ?>><?php esc_html_e( 'Usado', 'm2base-repuestos' ); ?></option>
					<option value="reacondicionado" <?php selected( $condicion, 'reacondicionado' ); ?>><?php esc_html_e( 'Reacondicionado', 'm2base-repuestos' ); ?></option>
				</select>
			</div>
		</div>
		<p class="description">
			<?php esc_html_e( 'La compatibilidad por marca y modelo se define en las cajas "Marcas de vehículo" y "Modelos de vehículo" en la barra lateral.', 'm2base-repuestos' ); ?>
		</p>
		<?php
	}

	public static function guardar( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_NAME ], self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( array_keys( self::campos() ) as $clave ) {
			$campo = 'm2base_' . $clave;
			if ( isset( $_POST[ $campo ] ) ) {
				update_post_meta( $post_id, '_m2base_' . $clave, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
			}
		}

		if ( isset( $_POST['m2base_condicion'] ) ) {
			$condicion = sanitize_text_field( wp_unslash( $_POST['m2base_condicion'] ) );
			if ( in_array( $condicion, array( 'nuevo', 'usado', 'reacondicionado' ), true ) ) {
				update_post_meta( $post_id, '_m2base_condicion', $condicion );
			}
		}
	}
}
