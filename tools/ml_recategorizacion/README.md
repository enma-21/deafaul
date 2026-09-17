# Recategorización masiva — Masterbrake1937 (Mercado Libre Venezuela)

Script para corregir en bloque las `category_id` de las publicaciones mal
categorizadas del catálogo de **Masterbrake1937** (autopartes, cuenta MLV),
a partir del Excel `Masterbrake1937_categorizacion_v2.xlsx` (pestaña
**"Confirmadas (alta confianza)"**, 1,180 publicaciones).

## Bloqueo actual: acceso a la API

Mercado Libre confirmó por escrito que **DevCenter (crear apps de
API) no está disponible para cuentas de Venezuela**, así que desde la
propia cuenta de Masterbrake1937 no se puede generar un `client_id` /
`client_secret`.

Este script asume que ese bloqueo ya se resolvió por una de estas vías:

1. **App creada desde una cuenta de otro país**, autorizada vía OAuth
   contra la cuenta vendedora de Masterbrake1937 (el flujo de
   autorización de Mercado Libre permite que una app registrada en un
   sitio autorice el acceso a cualquier cuenta que complete el login,
   independientemente de dónde se creó la app).
2. **Vía el asesor de cuenta de Mercado Libre**, si en cambio se opta
   por la alternativa de enviarle la "Planilla de Mapeo" (ya generada
   en la otra pestaña del mismo Excel) para que ellos apliquen los
   cambios de categoría de su lado. En ese caso este script no aplica.

Hasta que exista un `access_token` OAuth válido con permisos sobre la
cuenta de Masterbrake1937, el script solo puede ejecutarse en modo
`--dry-run` (simulación, sin llamadas reales a la API).

## Instalación

```bash
cd tools/ml_recategorizacion
pip install -r requirements.txt
cp .env.example .env   # completar y hacer `source .env` o exportar las vars
```

## Obtener el access_token (una vez resuelto el acceso)

1. Crear la app en DevCenter desde la cuenta autorizada de otro país,
   con el redirect URI que vayas a usar.
2. Llevar a la cuenta de Masterbrake1937 por la URL de autorización:
   `https://auth.mercadolibre.com.ve/authorization?response_type=code&client_id=<CLIENT_ID>&redirect_uri=<REDIRECT_URI>`
3. Intercambiar el `code` recibido por `access_token` / `refresh_token`
   contra `POST https://api.mercadolibre.com/oauth/token`
   (`grant_type=authorization_code`).
4. Guardar `access_token` y `refresh_token`. El `access_token` expira
   a las 6 horas — usar `refresh_token.py` para renovarlo sin repetir
   el login:

   ```bash
   python refresh_token.py \
     --client-id "$ML_CLIENT_ID" \
     --client-secret "$ML_CLIENT_SECRET" \
     --refresh-token "$ML_REFRESH_TOKEN"
   ```

## Uso del script principal

**Siempre correr primero en `--dry-run`** para validar que el script
detecta bien las columnas y las 1,180 filas antes de tocar nada real:

```bash
python recategorize_listings.py \
  --excel /ruta/a/Masterbrake1937_categorizacion_v2.xlsx \
  --sheet "Confirmadas (alta confianza)" \
  --dry-run
```

Revisar el reporte generado (`..._resultado.xlsx`, pestaña
`Resultado Actualizacion`) y luego correr en real:

```bash
export ML_ACCESS_TOKEN="..."
python recategorize_listings.py \
  --excel /ruta/a/Masterbrake1937_categorizacion_v2.xlsx \
  --sheet "Confirmadas (alta confianza)"
```

Recomendado probar primero con pocas filas:

```bash
python recategorize_listings.py --excel ... --limit 10
```

### Qué hace

- Lee cada fila de la pestaña indicada, tomando el **ID de la
  publicación** y el **ID de categoría destino** (detecta el nombre de
  columna automáticamente; si no lo reconoce, indicar con `--item-col`
  / `--target-col`).
- Por cada publicación hace `PUT https://api.mercadolibre.com/items/{ITEM_ID}`
  con `{"category_id": "<nueva_categoria>"}`.
- Reintenta automáticamente ante `429` (rate limit) y errores `5xx`
  con backoff exponencial, respetando `Retry-After` si Mercado Libre lo
  envía.
- **Nunca modifica el Excel de entrada**: genera un archivo nuevo
  `<nombre>_resultado.xlsx` con una pestaña `Resultado Actualizacion`
  que registra, fila por fila: estado (`OK` / `ERROR` / `DRY_RUN`),
  código HTTP, detalle del error y, si aplica, los **atributos
  faltantes** que la categoría nueva exige y la anterior no exigía
  (Mercado Libre devuelve esto en el campo `cause` del error 400 —
  el script lo extrae y lo deja legible en la columna `Atributos
  Faltantes`).
- Controla el ritmo de requests con `--sleep` (default 0.3s entre
  publicaciones) para no chocar con el rate limit de la API.

### Publicaciones con atributos faltantes

Cuando la categoría destino pide un atributo que la categoría actual
no pedía (ej. `BRAND`, `MODEL`, `VEHICLE_TYPE`), Mercado Libre rechaza
el `PUT` con un error 400 y el script lo deja registrado en el reporte
en vez de fallar todo el proceso. Esas filas quedan con `Status =
ERROR` y el detalle en `Atributos Faltantes`; hay que completarlas a
mano (o en una segunda pasada del Excel con esos datos) y volver a
correr el script solo para esas filas.

## Archivos

- `recategorize_listings.py` — script principal.
- `refresh_token.py` — renueva el access_token usando el refresh_token.
- `requirements.txt` — dependencias (`requests`, `openpyxl`).
- `.env.example` — variables de entorno esperadas.
