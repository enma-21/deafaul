<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="contenido-principal" class="m2base-main">
	<div class="m2base-contenedor m2base-archivo">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'm2base-entrada' ); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="m2base-entrada__extracto"><?php the_excerpt(); ?></div>
				</article>
				<?php
			endwhile;
			the_posts_pagination();
			?>
		<?php else : ?>
			<p class="m2base-sin-resultados"><?php esc_html_e( 'No hay contenido para mostrar.', 'm2base-repuestos-theme' ); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
