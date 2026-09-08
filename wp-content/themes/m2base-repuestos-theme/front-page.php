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
			</div>
		</div>
	</section>

	<section class="m2base-seccion-buscador">
		<div class="m2base-contenedor">
			<?php if ( m2base_theme_plugin_activo() ) : ?>
				<?php echo do_shortcode( '[m2base_buscador_repuestos]' ); ?>
			<?php else : ?>
				<p class="m2base-aviso"><?php esc_html_e( 'Activa el plugin M2Base Repuestos para mostrar el buscador aquí.', 'm2base-repuestos-theme' ); ?></p>
			<?php endif; ?>
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
					$imagen  = get_template_directory_uri() . '/assets/images/marcas/' . $marca['archivo'];
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

	<?php if ( m2base_theme_plugin_activo() ) :
		$categorias = get_terms(
			array(
				'taxonomy'   => 'categoria_repuesto',
				'hide_empty' => true,
				'number'     => 6,
			)
		);
		if ( ! is_wp_error( $categorias ) && ! empty( $categorias ) ) :
			?>
			<section class="m2base-categorias">
				<div class="m2base-contenedor">
					<h2 class="m2base-seccion-titulo"><?php esc_html_e( 'Compra por categoría', 'm2base-repuestos-theme' ); ?></h2>
					<div class="m2base-categorias__grid">
						<?php foreach ( $categorias as $categoria ) : ?>
							<a class="m2base-categorias__item" href="<?php echo esc_url( get_term_link( $categoria ) ); ?>">
								<?php echo m2base_theme_icon( m2base_theme_icono_categoria( $categoria->name ), 'm2base-categorias__icono' ); ?>
								<span><?php echo esc_html( $categoria->name ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$destacados = new WP_Query(
			array(
				'post_type'      => 'repuesto',
				'posts_per_page' => 8,
				'post_status'    => 'publish',
			)
		);
		if ( $destacados->have_posts() ) :
			?>
			<section class="m2base-destacados">
				<div class="m2base-contenedor">
					<h2 class="m2base-seccion-titulo"><?php esc_html_e( 'Últimos repuestos agregados', 'm2base-repuestos-theme' ); ?></h2>
					<?php echo M2Base_Repuestos_Render::resultados_html( $destacados ); ?>
				</div>
			</section>
			<?php
		endif;
	endif;
	?>

	<section class="m2base-pagos">
		<div class="m2base-contenedor m2base-pagos__inner">
			<h2 class="m2base-seccion-titulo"><?php esc_html_e( 'Formas de pago (próximamente)', 'm2base-repuestos-theme' ); ?></h2>
			<div class="m2base-pagos__chips">
				<?php foreach ( m2base_theme_medios_de_pago() as $medio ) : ?>
					<div class="m2base-pagos__chip">
						<strong><?php echo esc_html( $medio['nombre'] ); ?></strong>
						<span><?php echo esc_html( $medio['estado'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="m2base-pagos__nota"><?php esc_html_e( 'Por ahora, escríbenos por WhatsApp o teléfono para cotizar y coordinar tu compra.', 'm2base-repuestos-theme' ); ?></p>
		</div>
	</section>

</main>

<?php
get_footer();
