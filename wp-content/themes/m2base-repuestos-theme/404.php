<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="contenido-principal" class="m2base-main">
	<div class="m2base-contenedor m2base-404">
		<h1><?php esc_html_e( 'Página no encontrada', 'm2base-repuestos-theme' ); ?></h1>
		<p><?php esc_html_e( 'El repuesto o la página que buscas no existe. Intenta buscar de nuevo desde el inicio.', 'm2base-repuestos-theme' ); ?></p>
		<a class="m2base-boton m2base-boton--principal" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Volver al inicio', 'm2base-repuestos-theme' ); ?></a>
	</div>
</main>

<?php
get_footer();
