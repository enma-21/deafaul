<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class M2Base_Repuestos_CPT {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'modelo_vehiculo_add_form_fields', array( __CLASS__, 'field_marca_padre_nuevo' ) );
		add_action( 'modelo_vehiculo_edit_form_fields', array( __CLASS__, 'field_marca_padre_editar' ) );
		add_action( 'created_modelo_vehiculo', array( __CLASS__, 'guardar_marca_padre' ) );
		add_action( 'edited_modelo_vehiculo', array( __CLASS__, 'guardar_marca_padre' ) );
	}

	/**
	 * "Modelo" no es jerárquico dentro de la taxonomía nativa de WP, así que
	 * la relación modelo -> marca se guarda como term meta para poder filtrar
	 * los modelos disponibles según la marca elegida en el buscador (AJAX).
	 */
	public static function field_marca_padre_nuevo() {
		?>
		<div class="form-field">
			<label for="m2base_marca_padre"><?php esc_html_e( 'Marca asociada', 'm2base-repuestos' ); ?></label>
			<?php
			wp_dropdown_categories(
				array(
					'taxonomy'         => 'marca_vehiculo',
					'name'             => 'm2base_marca_padre',
					'id'               => 'm2base_marca_padre',
					'show_option_none' => __( 'Selecciona una marca', 'm2base-repuestos' ),
					'hide_empty'       => false,
				)
			);
			?>
			<p><?php esc_html_e( 'Marca de vehículo a la que pertenece este modelo.', 'm2base-repuestos' ); ?></p>
		</div>
		<?php
	}

	public static function field_marca_padre_editar( $term ) {
		$marca_id = get_term_meta( $term->term_id, 'marca_id', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="m2base_marca_padre"><?php esc_html_e( 'Marca asociada', 'm2base-repuestos' ); ?></label></th>
			<td>
				<?php
				wp_dropdown_categories(
					array(
						'taxonomy'         => 'marca_vehiculo',
						'name'             => 'm2base_marca_padre',
						'id'               => 'm2base_marca_padre',
						'show_option_none' => __( 'Selecciona una marca', 'm2base-repuestos' ),
						'hide_empty'       => false,
						'selected'         => $marca_id,
					)
				);
				?>
			</td>
		</tr>
		<?php
	}

	public static function guardar_marca_padre( $term_id ) {
		if ( isset( $_POST['m2base_marca_padre'] ) ) {
			update_term_meta( $term_id, 'marca_id', absint( $_POST['m2base_marca_padre'] ) );
		}
	}

	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Repuestos', 'm2base-repuestos' ),
			'singular_name'      => __( 'Repuesto', 'm2base-repuestos' ),
			'add_new_item'       => __( 'Añadir nuevo repuesto', 'm2base-repuestos' ),
			'edit_item'          => __( 'Editar repuesto', 'm2base-repuestos' ),
			'new_item'           => __( 'Nuevo repuesto', 'm2base-repuestos' ),
			'view_item'          => __( 'Ver repuesto', 'm2base-repuestos' ),
			'search_items'       => __( 'Buscar repuestos', 'm2base-repuestos' ),
			'not_found'          => __( 'No se encontraron repuestos', 'm2base-repuestos' ),
			'all_items'          => __( 'Todos los repuestos', 'm2base-repuestos' ),
			'menu_name'          => __( 'Repuestos', 'm2base-repuestos' ),
		);

		register_post_type(
			'repuesto',
			array(
				'labels'        => $labels,
				'public'        => true,
				'has_archive'   => true,
				'rewrite'       => array( 'slug' => 'repuestos' ),
				'menu_icon'     => 'dashicons-car',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'show_in_rest'  => true,
				'menu_position' => 5,
			)
		);
	}

	public static function register_taxonomies() {
		register_taxonomy(
			'categoria_repuesto',
			'repuesto',
			array(
				'labels'            => array(
					'name'          => __( 'Categorías de repuesto', 'm2base-repuestos' ),
					'singular_name' => __( 'Categoría', 'm2base-repuestos' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'categoria-repuesto' ),
			)
		);

		register_taxonomy(
			'marca_vehiculo',
			'repuesto',
			array(
				'labels'            => array(
					'name'          => __( 'Marcas de vehículo', 'm2base-repuestos' ),
					'singular_name' => __( 'Marca', 'm2base-repuestos' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'marca' ),
			)
		);

		register_taxonomy(
			'modelo_vehiculo',
			'repuesto',
			array(
				'labels'            => array(
					'name'          => __( 'Modelos de vehículo', 'm2base-repuestos' ),
					'singular_name' => __( 'Modelo', 'm2base-repuestos' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'modelo' ),
			)
		);
	}
}
