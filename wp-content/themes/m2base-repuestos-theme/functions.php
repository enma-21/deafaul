<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'M2BASE_THEME_VERSION', '1.0.0' );

function m2base_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'customize-selective-refresh-widgets' );

	register_nav_menus(
		array(
			'principal' => __( 'Menú principal', 'm2base-repuestos-theme' ),
			'footer'    => __( 'Menú de pie de página', 'm2base-repuestos-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'm2base_theme_setup' );

function m2base_theme_assets() {
	wp_enqueue_style( 'm2base-theme-style', get_stylesheet_uri(), array(), M2BASE_THEME_VERSION );
	wp_enqueue_script( 'm2base-theme-script', get_template_directory_uri() . '/assets/js/theme.js', array(), M2BASE_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'm2base_theme_assets' );

function m2base_theme_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Pie de página', 'm2base-repuestos-theme' ),
			'id'            => 'pie-de-pagina',
			'before_widget' => '<div class="m2base-footer__widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="m2base-footer__widget-titulo">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'm2base_theme_widgets_init' );

/**
 * El tema se apoya en el plugin M2Base Repuestos para el CPT, taxonomías y
 * el render de tarjetas/buscador; sin el plugin activo, las plantillas de
 * repuesto caen a un mensaje de aviso en vez de romper el sitio.
 */
function m2base_theme_plugin_activo() {
	return class_exists( 'M2Base_Repuestos_Render' );
}

function m2base_theme_aviso_plugin() {
	if ( ! m2base_theme_plugin_activo() && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="m2base-aviso">' . esc_html__( 'Activa el plugin "M2Base Repuestos" para mostrar el catálogo y el buscador.', 'm2base-repuestos-theme' ) . '</div>';
	}
}
add_action( 'wp_body_open', 'm2base_theme_aviso_plugin' );

function m2base_theme_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'm2base_contacto',
		array(
			'title'    => __( 'Contacto y redes sociales', 'm2base-repuestos-theme' ),
			'priority' => 30,
		)
	);

	$campos = array(
		'm2base_telefono'  => __( 'Teléfono', 'm2base-repuestos-theme' ),
		'm2base_email'     => __( 'Correo de contacto', 'm2base-repuestos-theme' ),
		'm2base_whatsapp'  => __( 'Enlace de WhatsApp', 'm2base-repuestos-theme' ),
		'm2base_facebook'  => __( 'URL de Facebook', 'm2base-repuestos-theme' ),
		'm2base_instagram' => __( 'URL de Instagram', 'm2base-repuestos-theme' ),
		'm2base_tiktok'    => __( 'URL de TikTok', 'm2base-repuestos-theme' ),
	);

	foreach ( $campos as $id => $etiqueta ) {
		$wp_customize->add_setting( $id, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control(
			$id,
			array(
				'label'   => $etiqueta,
				'section' => 'm2base_contacto',
				'type'    => 'text',
			)
		);
	}
}
add_action( 'customize_register', 'm2base_theme_customizer' );

function m2base_theme_medios_de_pago() {
	return array(
		array( 'nombre' => __( 'Tarjeta (Stripe)', 'm2base-repuestos-theme' ), 'estado' => __( 'próximamente', 'm2base-repuestos-theme' ) ),
		array( 'nombre' => __( 'Transferencia bancaria', 'm2base-repuestos-theme' ), 'estado' => __( 'próximamente', 'm2base-repuestos-theme' ) ),
	);
}
