<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="contenido-principal" class="m2base-main">
		<div class="m2base-contenedor m2base-pagina">
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
