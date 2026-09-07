<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="contenido-principal" class="m2base-main">
	<div class="m2base-contenedor m2base-archivo">
		<header class="m2base-archivo__cabecera">
			<h1><?php the_archive_title(); ?></h1>
			<?php the_archive_description( '<div class="m2base-archivo__descripcion">', '</div>' ); ?>
		</header>

		<?php if ( m2base_theme_plugin_activo() ) : ?>
			<?php echo do_shortcode( '[m2base_buscador_repuestos titulo=""]' ); ?>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="m2base-grid m2base-archivo__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					echo m2base_theme_plugin_activo() ? M2Base_Repuestos_Render::tarjeta_html( get_the_ID() ) : '';
				endwhile;
				?>
			</div>

			<div class="m2base-paginacion">
				<?php
				the_posts_pagination(
					array(
						'prev_text' => __( '← Anterior', 'm2base-repuestos-theme' ),
						'next_text' => __( 'Siguiente →', 'm2base-repuestos-theme' ),
					)
				);
				?>
			</div>
		<?php else : ?>
			<p class="m2base-sin-resultados"><?php esc_html_e( 'Todavía no hay repuestos publicados en esta sección.', 'm2base-repuestos-theme' ); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
