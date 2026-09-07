<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<footer class="m2base-footer">
		<div class="m2base-contenedor m2base-footer__inner">
			<div class="m2base-footer__col">
				<h3><?php bloginfo( 'name' ); ?></h3>
				<p><?php bloginfo( 'description' ); ?></p>
				<div class="m2base-footer__pagos">
					<?php foreach ( m2base_theme_medios_de_pago() as $medio ) : ?>
						<span class="m2base-footer__pago-chip"><?php echo esc_html( $medio['nombre'] ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="m2base-footer__col">
				<h3><?php esc_html_e( 'Enlaces', 'm2base-repuestos-theme' ); ?></h3>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'm2base-footer__menu',
						'fallback_cb'    => false,
					)
				);
				?>
			</div>

			<?php if ( is_active_sidebar( 'pie-de-pagina' ) ) : ?>
				<div class="m2base-footer__col">
					<?php dynamic_sidebar( 'pie-de-pagina' ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="m2base-contenedor m2base-footer__legal">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Todos los derechos reservados.', 'm2base-repuestos-theme' ); ?></p>
		</div>
	</footer>

<?php wp_footer(); ?>
</body>
</html>
