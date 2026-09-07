<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class M2Base_Repuestos_Search {

	const NONCE_ACTION = 'm2base_buscador_nonce';

	public static function init() {
		add_shortcode( 'm2base_buscador_repuestos', array( __CLASS__, 'shortcode_buscador' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		add_action( 'wp_ajax_m2base_buscar_repuestos', array( __CLASS__, 'ajax_buscar' ) );
		add_action( 'wp_ajax_nopriv_m2base_buscar_repuestos', array( __CLASS__, 'ajax_buscar' ) );

		add_action( 'wp_ajax_m2base_modelos_por_marca', array( __CLASS__, 'ajax_modelos_por_marca' ) );
		add_action( 'wp_ajax_nopriv_m2base_modelos_por_marca', array( __CLASS__, 'ajax_modelos_por_marca' ) );
	}

	public static function enqueue_assets() {
		wp_register_style( 'm2base-frontend', M2BASE_REPUESTOS_URL . 'assets/css/m2base-frontend.css', array(), M2BASE_REPUESTOS_VERSION );
		wp_register_script( 'm2base-search', M2BASE_REPUESTOS_URL . 'assets/js/m2base-search.js', array(), M2BASE_REPUESTOS_VERSION, true );

		wp_localize_script(
			'm2base-search',
			'M2BaseRepuestos',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => array(
					'buscando' => __( 'Buscando…', 'm2base-repuestos' ),
					'error'    => __( 'Ocurrió un error al buscar. Intenta nuevamente.', 'm2base-repuestos' ),
				),
			)
		);

		wp_enqueue_style( 'm2base-frontend' );
		wp_enqueue_script( 'm2base-search' );
	}

	public static function shortcode_buscador( $atts ) {
		$atts = shortcode_atts(
			array(
				'titulo' => __( 'Encuentra el repuesto exacto para tu vehículo', 'm2base-repuestos' ),
			),
			$atts,
			'm2base_buscador_repuestos'
		);

		$categorias = get_terms( array( 'taxonomy' => 'categoria_repuesto', 'hide_empty' => false ) );
		$marcas     = get_terms( array( 'taxonomy' => 'marca_vehiculo', 'hide_empty' => false ) );

		ob_start();
		?>
		<div class="m2base-buscador" data-m2base-buscador>
			<?php if ( ! empty( $atts['titulo'] ) ) : ?>
				<h2 class="m2base-buscador__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h2>
			<?php endif; ?>
			<form class="m2base-buscador__form" data-m2base-form>
				<div class="m2base-buscador__campo m2base-buscador__campo--texto">
					<label for="m2base-texto"><?php esc_html_e( 'Buscar por nombre o número de parte', 'm2base-repuestos' ); ?></label>
					<input type="text" id="m2base-texto" name="texto" placeholder="<?php esc_attr_e( 'Ej: pastillas de freno, filtro de aceite…', 'm2base-repuestos' ); ?>" />
				</div>
				<div class="m2base-buscador__campo">
					<label for="m2base-marca"><?php esc_html_e( 'Marca', 'm2base-repuestos' ); ?></label>
					<select id="m2base-marca" name="marca" data-m2base-marca>
						<option value=""><?php esc_html_e( 'Todas', 'm2base-repuestos' ); ?></option>
						<?php foreach ( $marcas as $marca ) : ?>
							<option value="<?php echo esc_attr( $marca->term_id ); ?>"><?php echo esc_html( $marca->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="m2base-buscador__campo">
					<label for="m2base-modelo"><?php esc_html_e( 'Modelo', 'm2base-repuestos' ); ?></label>
					<select id="m2base-modelo" name="modelo" data-m2base-modelo disabled>
						<option value=""><?php esc_html_e( 'Todos', 'm2base-repuestos' ); ?></option>
					</select>
				</div>
				<div class="m2base-buscador__campo">
					<label for="m2base-anio"><?php esc_html_e( 'Año', 'm2base-repuestos' ); ?></label>
					<input type="number" id="m2base-anio" name="anio" min="1950" max="2100" placeholder="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" />
				</div>
				<div class="m2base-buscador__campo">
					<label for="m2base-categoria"><?php esc_html_e( 'Categoría', 'm2base-repuestos' ); ?></label>
					<select id="m2base-categoria" name="categoria">
						<option value=""><?php esc_html_e( 'Todas', 'm2base-repuestos' ); ?></option>
						<?php foreach ( $categorias as $categoria ) : ?>
							<option value="<?php echo esc_attr( $categoria->term_id ); ?>"><?php echo esc_html( $categoria->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<button type="submit" class="m2base-buscador__boton"><?php esc_html_e( 'Buscar repuesto', 'm2base-repuestos' ); ?></button>
			</form>
			<div class="m2base-buscador__resultados" data-m2base-resultados></div>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function verificar_nonce() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => __( 'Sesión de búsqueda inválida, recarga la página.', 'm2base-repuestos' ) ), 403 );
		}
	}

	public static function construir_query( $params ) {
		$tax_query  = array( 'relation' => 'AND' );
		$meta_query = array( 'relation' => 'AND' );

		if ( ! empty( $params['marca'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'marca_vehiculo',
				'field'    => 'term_id',
				'terms'    => absint( $params['marca'] ),
			);
		}

		if ( ! empty( $params['modelo'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'modelo_vehiculo',
				'field'    => 'term_id',
				'terms'    => absint( $params['modelo'] ),
			);
		}

		if ( ! empty( $params['categoria'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'categoria_repuesto',
				'field'    => 'term_id',
				'terms'    => absint( $params['categoria'] ),
			);
		}

		if ( ! empty( $params['anio'] ) ) {
			$anio = absint( $params['anio'] );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'relation' => 'AND',
					array(
						'key'     => '_m2base_anio_desde',
						'value'   => $anio,
						'compare' => '<=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_m2base_anio_hasta',
						'value'   => $anio,
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				),
				array(
					'key'     => '_m2base_anio_desde',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$args = array(
			'post_type'      => 'repuesto',
			'post_status'    => 'publish',
			'posts_per_page' => 24,
			'paged'          => ! empty( $params['pagina'] ) ? absint( $params['pagina'] ) : 1,
		);

		if ( ! empty( $params['texto'] ) ) {
			$args['s'] = sanitize_text_field( $params['texto'] );
		}

		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query;
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}

		return new WP_Query( $args );
	}

	public static function ajax_buscar() {
		self::verificar_nonce();

		$params = array(
			'texto'     => isset( $_POST['texto'] ) ? sanitize_text_field( wp_unslash( $_POST['texto'] ) ) : '',
			'marca'     => isset( $_POST['marca'] ) ? absint( $_POST['marca'] ) : 0,
			'modelo'    => isset( $_POST['modelo'] ) ? absint( $_POST['modelo'] ) : 0,
			'categoria' => isset( $_POST['categoria'] ) ? absint( $_POST['categoria'] ) : 0,
			'anio'      => isset( $_POST['anio'] ) ? absint( $_POST['anio'] ) : 0,
			'pagina'    => isset( $_POST['pagina'] ) ? absint( $_POST['pagina'] ) : 1,
		);

		$query = self::construir_query( $params );

		wp_send_json_success(
			array(
				'html'  => M2Base_Repuestos_Render::resultados_html( $query ),
				'total' => (int) $query->found_posts,
			)
		);
	}

	public static function ajax_modelos_por_marca() {
		self::verificar_nonce();

		$marca_id = isset( $_POST['marca'] ) ? absint( $_POST['marca'] ) : 0;
		if ( ! $marca_id ) {
			wp_send_json_success( array( 'modelos' => array() ) );
		}

		$modelos = get_terms(
			array(
				'taxonomy'   => 'modelo_vehiculo',
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'   => 'marca_id',
						'value' => $marca_id,
					),
				),
			)
		);

		$lista = array();
		if ( ! is_wp_error( $modelos ) ) {
			foreach ( $modelos as $modelo ) {
				$lista[] = array( 'id' => $modelo->term_id, 'nombre' => $modelo->name );
			}
		}

		wp_send_json_success( array( 'modelos' => $lista ) );
	}
}
