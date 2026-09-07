<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$datos = m2base_theme_plugin_activo() ? M2Base_Repuestos_Render::obtener_datos( get_the_ID() ) : null;
	?>

	<main id="contenido-principal" class="m2base-main">
		<div class="m2base-contenedor m2base-producto">

			<nav class="m2base-migas" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'm2base-repuestos-theme' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'm2base-repuestos-theme' ); ?></a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'repuesto' ) ); ?>"><?php esc_html_e( 'Repuestos', 'm2base-repuestos-theme' ); ?></a>
				<span aria-hidden="true">/</span>
				<span><?php the_title(); ?></span>
			</nav>

			<div class="m2base-producto__grid">
				<div class="m2base-producto__imagen">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large' ); ?>
					<?php else : ?>
						<div class="m2base-card__placeholder m2base-producto__placeholder" aria-hidden="true">🔧</div>
					<?php endif; ?>
				</div>

				<div class="m2base-producto__info">
					<?php if ( $datos && ! empty( $datos['categorias'] ) ) : ?>
						<span class="m2base-card__categoria"><?php echo esc_html( $datos['categorias'][0] ); ?></span>
					<?php endif; ?>

					<h1><?php the_title(); ?></h1>

					<?php if ( $datos ) : ?>
						<p class="m2base-producto__precio"><?php echo esc_html( M2Base_Repuestos_Render::precio_formateado( $datos ) ); ?></p>

						<ul class="m2base-producto__ficha">
							<?php if ( $datos['sku'] ) : ?>
								<li><strong><?php esc_html_e( 'SKU:', 'm2base-repuestos-theme' ); ?></strong> <?php echo esc_html( $datos['sku'] ); ?></li>
							<?php endif; ?>
							<?php if ( $datos['numero_parte'] ) : ?>
								<li><strong><?php esc_html_e( 'N.º de parte OEM:', 'm2base-repuestos-theme' ); ?></strong> <?php echo esc_html( $datos['numero_parte'] ); ?></li>
							<?php endif; ?>
							<?php $compat = M2Base_Repuestos_Render::compatibilidad_formateada( $datos ); ?>
							<?php if ( $compat ) : ?>
								<li><strong><?php esc_html_e( 'Compatible con:', 'm2base-repuestos-theme' ); ?></strong> <?php echo esc_html( $compat ); ?></li>
							<?php endif; ?>
							<?php if ( ! empty( $datos['modelos'] ) ) : ?>
								<li><strong><?php esc_html_e( 'Modelos:', 'm2base-repuestos-theme' ); ?></strong> <?php echo esc_html( implode( ', ', $datos['modelos'] ) ); ?></li>
							<?php endif; ?>
							<?php if ( $datos['condicion'] ) : ?>
								<li><strong><?php esc_html_e( 'Condición:', 'm2base-repuestos-theme' ); ?></strong> <?php echo esc_html( ucfirst( $datos['condicion'] ) ); ?></li>
							<?php endif; ?>
							<li>
								<strong><?php esc_html_e( 'Disponibilidad:', 'm2base-repuestos-theme' ); ?></strong>
								<?php echo $datos['stock'] && (int) $datos['stock'] > 0 ? esc_html__( 'En stock', 'm2base-repuestos-theme' ) : esc_html__( 'Consultar disponibilidad', 'm2base-repuestos-theme' ); ?>
							</li>
						</ul>
					<?php endif; ?>

					<div class="m2base-producto__acciones">
						<?php $whatsapp = get_theme_mod( 'm2base_whatsapp' ); ?>
						<?php if ( $whatsapp ) : ?>
							<a class="m2base-boton m2base-boton--principal" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Consultar por WhatsApp', 'm2base-repuestos-theme' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( get_theme_mod( 'm2base_telefono' ) ) : ?>
							<a class="m2base-boton m2base-boton--secundario" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', get_theme_mod( 'm2base_telefono' ) ) ); ?>">
								<?php esc_html_e( 'Llamar para cotizar', 'm2base-repuestos-theme' ); ?>
							</a>
						<?php endif; ?>
					</div>
					<p class="m2base-producto__nota"><?php esc_html_e( 'La compra en línea con tarjeta y transferencia estará disponible próximamente.', 'm2base-repuestos-theme' ); ?></p>
				</div>
			</div>

			<?php if ( get_the_content() ) : ?>
				<div class="m2base-producto__descripcion">
					<h2><?php esc_html_e( 'Descripción', 'm2base-repuestos-theme' ); ?></h2>
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		</div>
	</main>

	<?php
endwhile;

get_footer();
