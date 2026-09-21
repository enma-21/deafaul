=== M2Base Bot de WhatsApp ===

Recibe mensajes de WhatsApp Business (Meta Cloud API) mediante un webhook público y responde consultando el catálogo ya sincronizado por **M2Base Catálogo Mercado Libre**. Guarda cada conversación y mensaje en tablas MySQL propias.

El generador de respuesta incluido es **provisional**: busca directamente en el catálogo por palabras clave / marca / modelo de vehículo, no es todavía una IA conversacional. Está aislado en `M2Wab_Responder::responder()` para poder reemplazarlo por un proveedor de IA (Claude, u otro) sin tocar el webhook, la seguridad ni la persistencia.

Requiere que el plugin **M2Base Catálogo Mercado Libre** esté activo con el catálogo ya sincronizado (Ajustes → Catálogo ML).

== Instalación ==

1. Sube la carpeta `m2base-whatsapp-bot` a `wp-content/plugins/` (por FTP) y actívala desde Plugins.
2. Abre Ajustes → Bot WhatsApp. La página genera automáticamente un "Verify token" la primera vez que se abre.

== Configuración del lado de Meta (pasos manuales, no automatizables) ==

1. Crea una cuenta en Meta for Developers y una App de tipo "Business".
2. Agrega el producto "WhatsApp" a la App.
3. Desde el panel de WhatsApp: toma el número de prueba gratuito, agrega hasta 5 números de destino de prueba, y copia:
   - El **Phone Number ID**.
   - El **WABA ID**.
   - El **token de acceso temporal** (dura 24h durante la fase de pruebas; hay que regenerarlo y actualizarlo aquí cada vez que expire).
   - El **App Secret** de la App (en Configuración básica de la App).
4. Pega esos cuatro datos en Ajustes → Bot WhatsApp de este plugin y guarda.
5. Vuelve al panel de Meta → WhatsApp → Configuration → Webhook: pega la URL del webhook y el Verify token que muestra esta página, guarda, y confirma que Meta lo valida (debe quedar en verde). Suscríbete al campo `messages`.
6. Envía un mensaje de texto desde uno de los números de prueba autorizados al número de WhatsApp de la App y confirma en esta misma página que el contador de "Mensajes últimas 24h" sube.

Para producción real (fuera de los 5 números de prueba) hace falta verificar el negocio en Meta Business Manager y generar un token permanente de System User — eso puede tardar días y es enteramente responsabilidad de Meta/el dueño del negocio.

== Seguridad del webhook ==

Toda solicitud POST entrante se valida contra la cabecera `X-Hub-Signature-256` usando el App Secret configurado (HMAC-SHA256 sobre el cuerpo crudo); si el App Secret no está configurado o la firma no coincide, se rechaza con 403. Los mensajes se procesan por `wa_message_id`, así que un reintento de entrega de Meta (por ejemplo si el sitio tardó en responder) no genera una respuesta duplicada.

== Límites de esta versión ==

- Solo procesa mensajes de tipo texto; a otros tipos (imagen, audio, ubicación, etc.) responde con un mensaje pidiendo texto.
- El "entendimiento" del mensaje es una búsqueda directa (reutiliza el mismo reconocedor de marca/modelo/año de vehículo del buscador del catálogo), no una conversación con memoria real más allá de recordar la última marca/modelo mencionados.
- No hay plantillas de mensaje ni envío proactivo — solo responde a mensajes entrantes, dentro de la ventana de servicio al cliente de WhatsApp.
