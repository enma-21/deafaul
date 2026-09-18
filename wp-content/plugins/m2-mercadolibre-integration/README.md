# M2 Mercado Libre Integration

Plugin base para Mercado Libre Colombia.

Incluye:

- OAuth Authorization Code con PKCE.
- Renovación automática del access token usando refresh token.
- Callback `GET /mercadolibre/callback`.
- Callback `POST /mercadolibre/notifications`.
- Pantalla de configuración en `Ajustes -> Mercado Libre`.
- Endpoint administrativo de estado: `/wp-json/m2-mercadolibre/v1/status`.
- **Recategorización masiva**: subir un CSV (ID de publicación + ID de categoría destino) y actualizar `category_id` en Mercado Libre en lotes, con reintentos y reporte descargable.

Después de activarlo:

1. Abre `Ajustes -> Mercado Libre`.
2. Introduce el Client ID y Client Secret de la aplicación.
3. Guarda la configuración.
4. Pulsa `Conectar Mercado Libre`.
5. En la aplicación de Mercado Libre usa las URLs mostradas por el plugin.

## Recategorización masiva

1. Exporta la pestaña "Planilla de Mapeo" del Excel de categorización a **CSV** (Archivo → Descargar → CSV en Google Sheets, o "Guardar como" en Excel), con dos columnas: el ID de la publicación y el ID de categoría destino.
2. En `Ajustes -> Mercado Libre`, sección "Recategorización masiva", sube ese CSV.
3. La página procesa automáticamente en lotes de 10 publicaciones cada vez que se carga, y se auto-refresca cada pocos segundos mientras haya pendientes — deja la pestaña abierta hasta que termine.
4. Cuando la cola queda en 0 pendientes, descarga el CSV de resultados para ver qué publicaciones fallaron y por qué (incluye los atributos faltantes que Mercado Libre exige en la categoría nueva).
5. "Reiniciar cola" borra el progreso actual si necesitas volver a cargar un CSV distinto.
