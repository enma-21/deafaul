# M2Base Repuestos — Plugin + Tema para WordPress

Base de un sitio de venta de repuestos de vehículos sobre WordPress: un plugin interno de catálogo/buscador y un tema simple y moderno para mostrarlo. Pensado para conectar más adelante pagos con Stripe y transferencia bancaria mediante WooCommerce.

## Qué incluye este repositorio

```
wp-content/
├── plugins/
│   └── m2base-repuestos/        # Catálogo, taxonomías y buscador (independiente del tema)
└── themes/
    └── m2base-repuestos-theme/  # Tema visual: portada con hero + buscador, listados y ficha de producto
```

### Plugin `m2base-repuestos`

- Tipo de contenido **Repuesto** con ficha técnica: SKU, número de parte OEM, precio referencial, año desde/hasta, condición (nuevo/usado/reacondicionado) y stock.
- Taxonomías: **Categoría de repuesto**, **Marca de vehículo** y **Modelo de vehículo** (cada modelo se asocia a una marca).
- Shortcode `[m2base_buscador_repuestos]`: formulario de búsqueda por texto, marca, modelo (se filtra según la marca elegida), año y categoría, con resultados por AJAX sin recargar la página.
- No depende del tema: funciona en cualquier tema de WordPress.

### Tema `m2base-repuestos-theme`

- Header con contacto, redes sociales y menú responsive.
- Portada (`front-page.php`) con hero, el buscador del plugin, categorías destacadas y últimos repuestos.
- Listado (`archive-repuesto.php`) y ficha de producto (`single-repuesto.php`) con botón de "Consultar por WhatsApp" / teléfono (mientras no haya cobro en línea).
- Sección de "Formas de pago (próximamente)" ya visible en la portada, para preparar a los clientes para Stripe y transferencia.
- Colores, teléfono, correo, WhatsApp y redes sociales se configuran desde **Apariencia > Personalizar** (no hace falta tocar código).

## Instalación en WordPress

1. Necesitas un WordPress ya instalado en el hosting (WordPress core no está en este repositorio; lo entrega el hosting o se instala desde `wp-admin` / el instalador de tu proveedor).
2. Sube el contenido de la carpeta `wp-content/` de este repositorio dentro del `wp-content/` de tu instalación (en tu hosting, dentro de `public_html/wp-content/`):
   - `wp-content/plugins/m2base-repuestos` → `public_html/wp-content/plugins/m2base-repuestos`
   - `wp-content/themes/m2base-repuestos-theme` → `public_html/wp-content/themes/m2base-repuestos-theme`
3. En `wp-admin`:
   - **Plugins** → activa "M2Base Repuestos".
   - **Apariencia > Temas** → activa "M2Base Repuestos".
   - **Apariencia > Personalizar** → completa teléfono, correo, WhatsApp y redes sociales.
4. Carga tus marcas, modelos y categorías desde el menú **Repuestos** del escritorio, y luego crea los repuestos con su ficha técnica.
5. Crea una página de inicio estática (o usa la portada por defecto) — el archivo `front-page.php` del tema ya muestra el buscador automáticamente.

### Subir los archivos por FTP

Con un cliente FTP (FileZilla, Cyberduck, o el administrador de archivos de tu hosting):

- Conéctate al hosting con el host, usuario y puerto que te dio tu proveedor.
- **La contraseña FTP nunca debe compartirse en un chat, commit o archivo del repositorio** — ingrésala solo directamente en tu cliente FTP o en el panel del hosting. Si en algún momento se compartió por accidente, cámbiala desde el panel de tu hosting antes de usarla.
- Sube el contenido de `wp-content/` de este repo dentro de la carpeta `public_html/wp-content/` de tu hosting, respetando la misma estructura de carpetas.

## Alcance actual (v1)

Esta primera entrega cubre **solo catálogo y buscador público**, sin carrito de compras ni cobro en línea, tal como se acordó.

## Próximos pasos: pagos con Stripe y transferencia bancaria

La recomendación es **no reinventar el carrito ni el checkout**: activar **WooCommerce** (plugin gratuito y muy usado) y conectarlo al mismo catálogo de repuestos que ya existe.

1. Instalar y activar **WooCommerce**.
2. Para cada `Repuesto` que quieras vender, crear su equivalente como producto simple de WooCommerce (mismo SKU, precio y nombre) — o, si el catálogo crece mucho, migrar `repuesto` a un producto de WooCommerce vía un pequeño script de importación (se puede construir cuando llegue el momento).
3. Activar los métodos de pago nativos de WooCommerce:
   - **Transferencia bancaria directa (BACS)**: viene incluido en WooCommerce, solo se configuran los datos bancarios.
   - **Stripe**: instalar el plugin oficial "WooCommerce Stripe Payment Gateway" y conectar la cuenta de Stripe (tarjeta de crédito/débito).
4. Actualizar el tema para mostrar "Agregar al carrito" / precios de WooCommerce en `single-repuesto.php` y `archive-repuesto.php` en lugar de (o junto a) los botones de WhatsApp actuales.

Este camino evita construir y mantener manualmente seguridad de pagos (PCI, tokenización, etc.), ya que Stripe y WooCommerce se encargan de eso.
