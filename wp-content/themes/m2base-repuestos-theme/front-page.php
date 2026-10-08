<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="contenido-principal" class="m2base-main">

	<section class="m2base-hero">
		<span class="m2base-hero__decoracion"><?php echo m2base_theme_icon( 'motor' ); ?></span>
		<div class="m2base-contenedor m2base-hero__inner">
			<div class="m2base-hero__texto">
				<span class="m2base-hero__etiqueta"><?php esc_html_e( 'Repuestos originales y alternativos', 'm2base-repuestos-theme' ); ?></span>
				<h1><?php echo esc_html( get_theme_mod( 'm2base_hero_titulo', __( 'El repuesto exacto para tu vehículo, en un solo lugar', 'm2base-repuestos-theme' ) ) ); ?></h1>
				<p><?php echo esc_html( get_theme_mod( 'm2base_hero_subtitulo', __( 'Busca por marca, modelo y año, y encuentra piezas nuevas, usadas y reacondicionadas con disponibilidad confirmada.', 'm2base-repuestos-theme' ) ) ); ?></p>
				<a href="#buscador-filtros" class="m2base-hero__cta"><?php esc_html_e( 'Ir al buscador', 'm2base-repuestos-theme' ); ?></a>
				<div class="m2base-hero__cashea">
					<span><?php esc_html_e( 'Cómpralo hoy en cuotas sin interés con:', 'm2base-repuestos-theme' ); ?></span>
					<img src="https://testproyect.m2base.com/wp-content/uploads/2026/10/logo-cashea.png" alt="Cashea" class="m2base-hero__cashea-logo">
				</div>
			</div>
		</div>
	</section>

	<section class="m2base-cifras">
		<div class="m2base-contenedor m2base-cifras__grid">
			<a class="m2base-reputacion" href="https://www.mercadolibre.com.ve/pagina/masterbrake1937" target="_blank" rel="noopener">
				<span class="m2base-reputacion__etiqueta"><?php esc_html_e( 'Reputación', 'm2base-repuestos-theme' ); ?></span>
				<span class="m2base-reputacion__titulo">
					<?php esc_html_e( 'MercadoLíder Platinum', 'm2base-repuestos-theme' ); ?>
					<svg class="m2base-reputacion__check" viewBox="0 0 24 24" aria-hidden="true">
						<circle cx="12" cy="12" r="11" fill="#00a650" />
						<path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</span>
				<span class="m2base-reputacion__medidor" aria-hidden="true">
					<span class="m2base-reputacion__segmento m2base-reputacion__segmento--1"></span>
					<span class="m2base-reputacion__segmento m2base-reputacion__segmento--2"></span>
					<span class="m2base-reputacion__segmento m2base-reputacion__segmento--3"></span>
					<span class="m2base-reputacion__segmento m2base-reputacion__segmento--4"></span>
				</span>
				<span class="m2base-reputacion__enlace"><?php esc_html_e( 'Ir a Reputación', 'm2base-repuestos-theme' ); ?> ›</span>
			</a>
			<?php
			$productos_disponibles = class_exists( 'M2Base_Catalogo_ML_Repository' )
				? '+' . number_format_i18n( M2Base_Catalogo_ML_Repository::contar_activos() )
				: null;
			foreach ( m2base_theme_numeros_confianza( $productos_disponibles ) as $cifra ) :
				?>
				<div class="m2base-cifras__item">
					<span class="m2base-cifras__numero"><?php echo esc_html( $cifra['numero'] ); ?></span>
					<span class="m2base-cifras__etiqueta"><?php echo esc_html( $cifra['etiqueta'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="m2base-como-funciona">
		<div class="m2base-contenedor">
			<h2 class="m2base-seccion-titulo m2base-seccion-titulo--centrado"><?php esc_html_e( 'Cómo funciona', 'm2base-repuestos-theme' ); ?></h2>
			<div class="m2base-como-funciona__grid">
				<div class="m2base-como-funciona__item">
					<span class="m2base-como-funciona__numero">1</span>
					<?php echo m2base_theme_icon( 'buscar', 'm2base-como-funciona__icono' ); ?>
					<h3><?php esc_html_e( 'Busca tu repuesto', 'm2base-repuestos-theme' ); ?></h3>
					<p><?php esc_html_e( 'Usa el buscador por marca, modelo, año o categoría y encuentra la pieza exacta que necesitas.', 'm2base-repuestos-theme' ); ?></p>
				</div>
				<div class="m2base-como-funciona__item">
					<span class="m2base-como-funciona__numero">2</span>
					<?php echo m2base_theme_icon( 'whatsapp', 'm2base-como-funciona__icono' ); ?>
					<h3><?php esc_html_e( 'Cotiza por WhatsApp', 'm2base-repuestos-theme' ); ?></h3>
					<p><?php esc_html_e( 'Escríbenos con el repuesto que te interesa y te confirmamos precio y disponibilidad al instante.', 'm2base-repuestos-theme' ); ?></p>
				</div>
				<div class="m2base-como-funciona__item">
					<span class="m2base-como-funciona__numero">3</span>
					<?php echo m2base_theme_icon( 'entrega', 'm2base-como-funciona__icono' ); ?>
					<h3><?php esc_html_e( 'Recíbelo', 'm2base-repuestos-theme' ); ?></h3>
					<p><?php esc_html_e( 'Coordinamos la entrega o el retiro en tienda, como prefieras.', 'm2base-repuestos-theme' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section class="m2base-marcas">
		<div class="m2base-contenedor">
			<h2 class="m2base-seccion-titulo m2base-seccion-titulo--centrado"><?php esc_html_e( 'Marcas que atendemos', 'm2base-repuestos-theme' ); ?></h2>
			<div class="m2base-marcas__grid">
				<?php foreach ( m2base_theme_marcas_logos() as $marca ) :
					$termino = m2base_theme_plugin_activo() ? get_term_by( 'slug', $marca['slug'], 'marca_vehiculo' ) : false;
					$enlace  = $termino && ! is_wp_error( $termino ) ? get_term_link( $termino ) : '';
					$imagen  = get_template_directory_uri() . '/assets/images/marcas/' . $marca['archivo'] . '?v=' . M2BASE_THEME_VERSION;
					?>
					<?php if ( $enlace ) : ?>
						<a class="m2base-marcas__item" href="<?php echo esc_url( $enlace ); ?>" aria-label="<?php echo esc_attr( $marca['nombre'] ); ?>">
							<img src="<?php echo esc_url( $imagen ); ?>" alt="<?php echo esc_attr( $marca['nombre'] ); ?>" loading="lazy" />
						</a>
					<?php else : ?>
						<span class="m2base-marcas__item">
							<img src="<?php echo esc_url( $imagen ); ?>" alt="<?php echo esc_attr( $marca['nombre'] ); ?>" loading="lazy" />
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section id="buscador-filtros" class="m2base-seccion-buscador m2base-seccion-buscador--delgada">
		<div class="m2base-contenedor">
			<?php if ( class_exists( 'M2Base_Catalogo_ML_Publico' ) ) : ?>
				<?php echo do_shortcode( '[m2base_catalogo_ml titulo=""]' ); ?>
			<?php else : ?>
				<p class="m2base-aviso"><?php esc_html_e( 'Activa el plugin M2Base Catálogo Mercado Libre para mostrar el buscador aquí.', 'm2base-repuestos-theme' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( class_exists( 'M2Base_Catalogo_ML_Repository' ) ) :
		$destacados_ml = M2Base_Catalogo_ML_Repository::destacados( 8 );
		if ( ! empty( $destacados_ml ) ) :
			wp_enqueue_style( 'm2mlc-frontend', M2MLC_URL . 'assets/css/m2base-catalogo-frontend.css', array(), M2MLC_VERSION );
			?>
			<section class="m2base-catalogo-ml">
				<div class="m2base-contenedor">
					<div class="m2mlc-grid">
						<?php foreach ( $destacados_ml as $item_ml ) : ?>
							<?php echo M2Base_Catalogo_ML_Render::tarjeta_html( $item_ml, true ); ?>
						<?php endforeach; ?>
					</div>
					<div class="m2base-catalogo-ml__cta">
						<a class="m2base-boton m2base-boton--principal" href="<?php echo esc_url( home_url( '/catalogo/' ) ); ?>"><?php esc_html_e( 'Ver catálogo completo', 'm2base-repuestos-theme' ); ?></a>
					</div>
				</div>
			</section>
			<?php
		endif;
	endif;
	?>

	<section class="m2base-pagos">
		<div class="m2base-contenedor m2base-pagos__inner">
			<h2 class="m2base-seccion-titulo"><?php esc_html_e( 'Métodos de Pago Aceptados', 'm2base-repuestos-theme' ); ?></h2>
			<div class="m2base-pagos__chips">
				<?php foreach ( m2base_theme_medios_de_pago() as $medio ) : ?>
					<div class="m2base-pagos__chip<?php echo ! empty( $medio['destacado'] ) ? ' m2base-pagos__chip--destacado' : ''; ?>">
						<?php if ( ! empty( $medio['logo'] ) ) : ?>
							<img class="m2base-pagos__chip-logo" src="<?php echo esc_url( $medio['logo'] ); ?>" alt="<?php echo esc_attr( $medio['nombre'] ); ?>">
						<?php endif; ?>
						<strong><?php echo esc_html( $medio['nombre'] ); ?></strong>
						<?php if ( ! empty( $medio['nota'] ) ) : ?>
							<span><?php echo esc_html( $medio['nota'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="m2base-pagos__nota"><?php esc_html_e( 'Escríbenos por WhatsApp o teléfono para cotizar y coordinar tu compra.', 'm2base-repuestos-theme' ); ?></p>
		</div>
	</section>

</main>

<?php
if ( class_exists( 'M2Base_Catalogo_ML_Publico' ) ) {
	wp_enqueue_style( 'm2mlc-frontend', M2MLC_URL . 'assets/css/m2base-catalogo-frontend.css', array(), M2MLC_VERSION );
	echo M2Base_Catalogo_ML_Publico::boton_flotante_html();
}

get_footer();
