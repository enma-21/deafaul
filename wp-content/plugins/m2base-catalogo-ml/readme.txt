=== M2Base Catálogo Mercado Libre ===

Sincroniza el catálogo completo de Mercado Libre a una base de datos MySQL propia (tablas nuevas, no usa post/postmeta de WordPress) y ofrece dos buscadores: uno interno para wp-admin, y uno público para el sitio web con botones de contacto por WhatsApp (embudo de ventas). Es también la base de datos que alimentará al futuro bot de WhatsApp con IA.

Requiere que el plugin **M2 Mercado Libre Integration** esté activo y conectado (Ajustes → Mercado Libre), porque reutiliza su token de acceso.

== Instalación ==

1. Sube la carpeta `m2base-catalogo-ml` a `wp-content/plugins/` (por FTP, igual que los otros plugins de este sitio) y actívala desde Plugins.
2. Verifica en Ajustes → Mercado Libre que la cuenta esté conectada.
3. Abre Ajustes → Catálogo ML para sincronizar, o Ajustes → Buscador catálogo ML para buscar/filtrar. Ambas pantallas requieren iniciar sesión como administrador — no son públicas.

== Sincronización ==

1. La primera vez, marca "Prueba: sincronizar solo 20 publicaciones" y pulsa "Iniciar sincronización" para verificar que todo funciona antes de traer el catálogo completo (~5,000 publicaciones).
2. Si la prueba se ve bien, pulsa "Detener / reiniciar sincronización" y vuelve a iniciar sin la casilla de prueba marcada para traer el catálogo completo.
3. La página procesa automáticamente en lotes cada vez que se carga, y se auto-refresca cada pocos segundos mientras haya trabajo pendiente — deja la pestaña abierta hasta que termine. Con ~5,000 publicaciones puede tardar entre 10 y 60 minutos según qué tan rápido responda la API de Mercado Libre.
4. "Detener / reiniciar sincronización" borra el progreso de la corrida actual, no el catálogo ya sincronizado.
5. No hay sincronización automática (cron) todavía: los precios y el stock quedan tan actualizados como la última vez que se apretó "Iniciar sincronización".

== Buscador interno (wp-admin) ==

Ajustes → Buscador catálogo ML, dentro del wp-admin, solo para administradores. Filtros disponibles: texto libre (también busca por número de parte), marca y modelo de vehículo (heurístico, ver abajo), categoría, rango de año y rango de precio. Cada resultado enlaza directamente a la publicación real en Mercado Libre.

== Buscador público (shortcode) ==

Shortcode `[m2base_catalogo_ml]`, usable en cualquier Página de WordPress (crea una página nueva, ej. "Catálogo", y pega el shortcode en el contenido) o directamente en una plantilla del tema con `do_shortcode('[m2base_catalogo_ml]')`. Acepta un atributo opcional `titulo` para cambiar el encabezado, ej. `[m2base_catalogo_ml titulo="Busca tu repuesto"]`.

No requiere iniciar sesión — mismos filtros que el buscador interno, mismo diseño visual (coincide con los colores del tema). Cada tarjeta de producto tiene un botón "Consultar por WhatsApp" con un mensaje prellenado mencionando ese repuesto específico, y la página muestra además un botón flotante general "Chatea con nosotros". El número de WhatsApp se configura en Apariencia → Personalizar → Contacto y redes sociales → "Número de WhatsApp para el catálogo" (por defecto usa el número actual de la empresa; se puede cambiar el día que haya un número dedicado para el bot de WhatsApp).

Este botón de WhatsApp es un enlace simple tipo "clic para chatear" (wa.me) — no depende de la API de WhatsApp Business ni de que la cuenta de Meta esté aprobada, funciona con cualquier número de WhatsApp normal.

== Sobre la marca/modelo de vehículo ==

Mercado Libre no expone la compatibilidad de vehículo como un dato estructurado: solo existe como texto libre dentro del título de la publicación (los atributos BRAND/PART_NUMBER de la API se refieren al fabricante del repuesto, no al vehículo). Por eso la marca/modelo/año que se muestran en el buscador son el resultado de un reconocimiento de texto sobre el título, no un dato garantizado por Mercado Libre. Después de la primera sincronización completa conviene revisar una muestra de filas en la base de datos (columna `vehicle_match_conf`: alta/media/baja) para estimar qué tan confiable es antes de promocionar los filtros de vehículo como exactos. Las publicaciones sin clasificar siguen siendo encontrables por texto y por categoría.

== Coexistencia con M2Base Repuestos ==

Este plugin no toca ni reemplaza `m2base-repuestos` (el catálogo curado a mano, pensado a futuro para venta con carrito). Quedan dos catálogos en paralelo a propósito: uno manual/curado, otro automático y completo (espejo de Mercado Libre) solo para búsqueda.
