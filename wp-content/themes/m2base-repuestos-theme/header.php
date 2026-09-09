<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
	<!-- Meta Pixel Code -->
	<script>
	!function(f,b,e,v,n,t,s)
	{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
	n.callMethod.apply(n,arguments):n.queue.push(arguments)};
	if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
	n.queue=[];t=b.createElement(e);t.async=!0;
	t.src=v;s=b.getElementsByTagName(e)[0];
	s.parentNode.insertBefore(t,s)}(window, document,'script',
	'https://connect.facebook.net/en_US/fbevents.js');
	fbq('init', '3052824495051443');
	fbq('track', 'PageView');
	</script>
	<noscript><img height="1" width="1" style="display:none"
	src="https://www.facebook.com/tr?id=3052824495051443&ev=PageView&noscript=1"
	/></noscript>
	<!-- End Meta Pixel Code -->
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="m2base-skip-link screen-reader-text" href="#contenido-principal"><?php esc_html_e( 'Ir al contenido', 'm2base-repuestos-theme' ); ?></a>

<header class="m2base-header">
	<div class="m2base-header__top">
		<div class="m2base-contenedor m2base-header__top-inner">
			<div class="m2base-header__contacto">
				<?php if ( get_theme_mod( 'm2base_telefono' ) ) : ?>
					<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', get_theme_mod( 'm2base_telefono' ) ) ); ?>"><?php echo m2base_theme_icon( 'telefono' ); ?><?php echo esc_html( get_theme_mod( 'm2base_telefono' ) ); ?></a>
				<?php endif; ?>
				<?php if ( get_theme_mod( 'm2base_email' ) ) : ?>
					<a href="mailto:<?php echo esc_attr( get_theme_mod( 'm2base_email' ) ); ?>"><?php echo m2base_theme_icon( 'correo' ); ?><?php echo esc_html( get_theme_mod( 'm2base_email' ) ); ?></a>
				<?php endif; ?>
			</div>
			<div class="m2base-header__redes">
				<?php
				$redes = array(
					'facebook'  => get_theme_mod( 'm2base_facebook' ),
					'instagram' => get_theme_mod( 'm2base_instagram' ),
					'tiktok'    => get_theme_mod( 'm2base_tiktok' ),
					'whatsapp'  => get_theme_mod( 'm2base_whatsapp' ),
				);
				foreach ( $redes as $red => $url ) :
					if ( $url ) :
						?>
						<a class="m2base-header__red" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $red ) ); ?>"><?php echo m2base_theme_icon( $red ); ?></a>
						<?php
					endif;
				endforeach;
				?>
			</div>
		</div>
	</div>

	<div class="m2base-contenedor m2base-header__principal">
		<div class="m2base-header__marca">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="m2base-header__logo-texto" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<button class="m2base-header__menu-toggle" type="button" aria-expanded="false" aria-controls="m2base-menu-principal" data-m2base-menu-toggle>
			<span></span><span></span><span></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Abrir menú', 'm2base-repuestos-theme' ); ?></span>
		</button>

		<nav class="m2base-header__nav" id="m2base-menu-principal" aria-label="<?php esc_attr_e( 'Menú principal', 'm2base-repuestos-theme' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'principal',
					'container'      => false,
					'menu_class'     => 'm2base-menu',
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>
	</div>
</header>
