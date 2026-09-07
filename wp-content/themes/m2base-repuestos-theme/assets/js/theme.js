( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var boton = document.querySelector( '[data-m2base-menu-toggle]' );
		var menu  = document.getElementById( 'm2base-menu-principal' );

		if ( ! boton || ! menu ) {
			return;
		}

		boton.addEventListener( 'click', function () {
			var abierto = menu.classList.toggle( 'm2base-header__nav--abierto' );
			boton.classList.toggle( 'm2base-header__menu-toggle--abierto', abierto );
			boton.setAttribute( 'aria-expanded', abierto ? 'true' : 'false' );
		} );
	} );
} )();
