( function () {
	'use strict';

	function onReady( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function buscar( contenedor, pagina ) {
		var form       = contenedor.querySelector( '[data-m2mlc-form]' );
		var resultados = contenedor.querySelector( '[data-m2mlc-resultados]' );
		var formData   = new FormData( form );
		var boton      = form.querySelector( '.m2mlc-buscador__boton' );
		var textoNodo  = boton.querySelector( '[data-m2mlc-boton-texto]' );
		var textoBoton = textoNodo ? textoNodo.textContent : boton.textContent;

		formData.append( 'action', 'm2mlc_buscar' );
		formData.append( 'nonce', window.M2MLCatalogo.nonce );
		formData.append( 'pagina', pagina || 1 );

		boton.disabled = true;
		if ( textoNodo ) {
			textoNodo.textContent = window.M2MLCatalogo.i18n.buscando;
		} else {
			boton.textContent = window.M2MLCatalogo.i18n.buscando;
		}
		resultados.setAttribute( 'aria-busy', 'true' );

		fetch( window.M2MLCatalogo.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( respuesta ) {
				return respuesta.json();
			} )
			.then( function ( datos ) {
				if ( datos && datos.success ) {
					resultados.innerHTML = datos.data.html;
				} else {
					resultados.innerHTML = '<p class="m2mlc-sin-resultados">' + window.M2MLCatalogo.i18n.error + '</p>';
				}
			} )
			.catch( function () {
				resultados.innerHTML = '<p class="m2mlc-sin-resultados">' + window.M2MLCatalogo.i18n.error + '</p>';
			} )
			.finally( function () {
				boton.disabled = false;
				if ( textoNodo ) {
					textoNodo.textContent = textoBoton;
				} else {
					boton.textContent = textoBoton;
				}
				resultados.removeAttribute( 'aria-busy' );
			} );
	}

	function actualizarModelos( contenedor ) {
		var selectMarca  = contenedor.querySelector( '[data-m2mlc-marca]' );
		var selectModelo = contenedor.querySelector( '[data-m2mlc-modelo]' );
		var marca        = selectMarca.value;

		selectModelo.innerHTML = '<option value="">' + selectModelo.dataset.placeholder + '</option>';

		if ( ! marca ) {
			selectModelo.disabled = true;
			return;
		}

		var formData = new FormData();
		formData.append( 'action', 'm2mlc_modelos_por_marca' );
		formData.append( 'nonce', window.M2MLCatalogo.nonce );
		formData.append( 'marca', marca );

		fetch( window.M2MLCatalogo.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( respuesta ) {
				return respuesta.json();
			} )
			.then( function ( datos ) {
				if ( datos && datos.success && datos.data.modelos.length ) {
					datos.data.modelos.forEach( function ( modelo ) {
						var opcion = document.createElement( 'option' );
						opcion.value = modelo.id;
						opcion.textContent = modelo.nombre;
						selectModelo.appendChild( opcion );
					} );
					selectModelo.disabled = false;
				} else {
					selectModelo.disabled = true;
				}
			} );
	}

	onReady( function () {
		var contenedores = document.querySelectorAll( '[data-m2mlc-buscador]' );

		contenedores.forEach( function ( contenedor ) {
			var form         = contenedor.querySelector( '[data-m2mlc-form]' );
			var selectModelo = contenedor.querySelector( '[data-m2mlc-modelo]' );

			if ( selectModelo ) {
				selectModelo.dataset.placeholder = selectModelo.options[ 0 ] ? selectModelo.options[ 0 ].textContent : '';
			}

			form.addEventListener( 'submit', function ( evento ) {
				evento.preventDefault();
				buscar( contenedor, 1 );
			} );

			var selectMarca = contenedor.querySelector( '[data-m2mlc-marca]' );
			if ( selectMarca ) {
				selectMarca.addEventListener( 'change', function () {
					actualizarModelos( contenedor );
				} );
			}

			// Carga inicial de resultados al entrar a la página.
			buscar( contenedor, 1 );
		} );
	} );
} )();
