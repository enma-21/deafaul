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
		var form        = contenedor.querySelector( '[data-m2base-form]' );
		var resultados  = contenedor.querySelector( '[data-m2base-resultados]' );
		var formData    = new FormData( form );
		var boton       = form.querySelector( '.m2base-buscador__boton' );
		var textoBoton  = boton.textContent;

		formData.append( 'action', 'm2base_buscar_repuestos' );
		formData.append( 'nonce', window.M2BaseRepuestos.nonce );
		formData.append( 'pagina', pagina || 1 );

		boton.disabled    = true;
		boton.textContent = window.M2BaseRepuestos.i18n.buscando;
		resultados.setAttribute( 'aria-busy', 'true' );

		fetch( window.M2BaseRepuestos.ajaxUrl, {
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
					resultados.innerHTML = '<p class="m2base-sin-resultados">' + window.M2BaseRepuestos.i18n.error + '</p>';
				}
			} )
			.catch( function () {
				resultados.innerHTML = '<p class="m2base-sin-resultados">' + window.M2BaseRepuestos.i18n.error + '</p>';
			} )
			.finally( function () {
				boton.disabled    = false;
				boton.textContent = textoBoton;
				resultados.removeAttribute( 'aria-busy' );
			} );
	}

	function actualizarModelos( contenedor ) {
		var selectMarca  = contenedor.querySelector( '[data-m2base-marca]' );
		var selectModelo = contenedor.querySelector( '[data-m2base-modelo]' );
		var marcaId      = selectMarca.value;

		selectModelo.innerHTML = '<option value="">' + selectModelo.dataset.placeholder + '</option>';

		if ( ! marcaId ) {
			selectModelo.disabled = true;
			return;
		}

		var formData = new FormData();
		formData.append( 'action', 'm2base_modelos_por_marca' );
		formData.append( 'nonce', window.M2BaseRepuestos.nonce );
		formData.append( 'marca', marcaId );

		fetch( window.M2BaseRepuestos.ajaxUrl, {
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
		var contenedores = document.querySelectorAll( '[data-m2base-buscador]' );

		contenedores.forEach( function ( contenedor ) {
			var form         = contenedor.querySelector( '[data-m2base-form]' );
			var selectModelo = contenedor.querySelector( '[data-m2base-modelo]' );

			if ( selectModelo ) {
				selectModelo.dataset.placeholder = selectModelo.options[ 0 ] ? selectModelo.options[ 0 ].textContent : '';
			}

			form.addEventListener( 'submit', function ( evento ) {
				evento.preventDefault();
				buscar( contenedor, 1 );
			} );

			var selectMarca = contenedor.querySelector( '[data-m2base-marca]' );
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
