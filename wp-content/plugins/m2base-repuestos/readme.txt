=== M2Base Repuestos ===
Contributors: m2base
Tags: repuestos, autopartes, catalogo, buscador
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Catálogo interno y buscador de repuestos de vehículos (marca, modelo, año, categoría, número de parte).

== Descripción ==

Este plugin agrega:

* Tipo de contenido "Repuesto" con ficha técnica (SKU, número de parte OEM, precio referencial, años de compatibilidad, condición, stock).
* Taxonomías: Categoría de repuesto, Marca de vehículo y Modelo de vehículo (el modelo queda ligado a su marca).
* Shortcode `[m2base_buscador_repuestos]` con un buscador por texto, marca, modelo (dependiente de la marca), año y categoría, con resultados vía AJAX sin recargar la página.
* Funciones de plantilla reutilizables (`M2Base_Repuestos_Render`) para que el tema muestre tarjetas de producto de forma consistente.

Este plugin NO incluye carrito de compras ni pasarela de pago: es la base del catálogo. Cuando el negocio esté listo para vender en línea, la ruta recomendada es activar WooCommerce y usar sus extensiones de Stripe y transferencia bancaria (BACS), reutilizando este mismo catálogo. Ver "Próximos pasos" en el README del repositorio.

== Instalación ==

1. Sube la carpeta `m2base-repuestos` a `wp-content/plugins/`.
2. Activa el plugin desde Apariencia > Plugins.
3. Carga tus marcas, modelos y categorías desde el menú "Repuestos" del escritorio.
4. Agrega el shortcode `[m2base_buscador_repuestos]` en cualquier página, o úsalo directamente en la portada del tema "M2Base Repuestos".
