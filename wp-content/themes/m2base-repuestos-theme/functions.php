<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'M2BASE_THEME_VERSION', '1.1.1' );

function m2base_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 160,
			'width'       => 160,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
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
	wp_enqueue_style(
		'm2base-theme-fonts',
		'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'm2base-theme-style', get_stylesheet_uri(), array( 'm2base-theme-fonts' ), M2BASE_THEME_VERSION );
	wp_enqueue_script( 'm2base-theme-script', get_template_directory_uri() . '/assets/js/theme.js', array(), M2BASE_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'm2base_theme_assets' );

/**
 * Íconos SVG en línea propios del tema (sin depender de librerías externas):
 * evita peticiones adicionales y permite colorearlos con `currentColor`.
 */
function m2base_theme_icon( $nombre, $clase = '' ) {
	$iconos = array(
		'buscar'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
		'telefono'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
		'correo'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>',
		'whatsapp'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.79.47 3.47 1.29 4.93L2 22l5.31-1.39a9.87 9.87 0 0 0 4.73 1.2h.01c5.46 0 9.9-4.45 9.9-9.9C21.96 6.45 17.5 2 12.04 2zm0 18.02h-.01a8.2 8.2 0 0 1-4.18-1.14l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.34c0-4.53 3.69-8.22 8.24-8.22 2.2 0 4.27.86 5.82 2.42a8.17 8.17 0 0 1 2.41 5.81c0 4.53-3.7 8.18-8.23 8.18zm4.51-6.16c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.13-.16.25-.64.81-.79.97-.14.16-.29.18-.54.06-.25-.12-1.04-.38-1.98-1.22-.73-.65-1.23-1.46-1.37-1.71-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.36-.77-1.86-.2-.48-.41-.42-.56-.43-.14-.01-.31-.01-.48-.01a.92.92 0 0 0-.67.31c-.23.25-.87.85-.87 2.07 0 1.22.89 2.4 1.02 2.56.12.16 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.14-1.18-.06-.11-.22-.17-.47-.29z"/></svg>',
		'facebook'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.77l-.44 2.89h-2.33v6.99A10 10 0 0 0 22 12z"/></svg>',
		'instagram'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
		'tiktok'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 2h-3.2v13.7a2.9 2.9 0 1 1-2.05-2.77V9.6a6.1 6.1 0 1 0 5.25 6.05V8.4a7.6 7.6 0 0 0 4.4 1.4V6.6a4.4 4.4 0 0 1-4.4-4.4z"/></svg>',
		'frenos'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>',
		'motor'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M3 13h2l1-3h4l1 3h6a2 2 0 0 1 2 2v2h-2"/><path d="M6 13v5h9v-5"/><circle cx="8" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/><path d="M17 10V7h-3"/></svg>',
		'suspension'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M7 3v4M7 21v-4M7 7c0 3 0 8 0 10"/><path d="M7 7l4 2-4 2 4 2-4 2"/><circle cx="17" cy="12" r="3"/></svg>',
		'filtros'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M4 4h16l-6 8v6l-4 2v-8z"/></svg>',
		'electrico'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h6l-1 8 11-14h-7z"/></svg>',
		'repuesto'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path d="M14.7 6.3a1 1 0 0 0 1.4 0l1.6-1.6a1 1 0 0 0-1.4-1.4l-1.6 1.6a1 1 0 0 0 0 1.4zM3 21l6-1 8.5-8.5-5-5L4 15l-1 6z"/></svg>',
	);

	if ( ! isset( $iconos[ $nombre ] ) ) {
		return '';
	}

	return '<span class="m2base-icono ' . esc_attr( $clase ) . '" aria-hidden="true">' . $iconos[ $nombre ] . '</span>';
}

/**
 * Asocia una categoría de repuesto a uno de los íconos disponibles buscando
 * coincidencias por nombre; si no hay coincidencia usa el ícono genérico.
 */
function m2base_theme_icono_categoria( $nombre_categoria ) {
	$nombre_categoria = remove_accents( mb_strtolower( $nombre_categoria ) );
	$mapa             = array(
		'freno'      => 'frenos',
		'motor'      => 'motor',
		'suspension' => 'suspension',
		'filtro'     => 'filtros',
		'electric'   => 'electrico',
		'bateria'    => 'electrico',
	);

	foreach ( $mapa as $clave => $icono ) {
		if ( false !== strpos( $nombre_categoria, $clave ) ) {
			return $icono;
		}
	}

	return 'repuesto';
}

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
