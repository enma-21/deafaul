# Tasks

## Active

- [ ] **Cambiar la optimización a "Maximizar el número de clientes potenciales"** - for Luis Miguel, due 2026-10-12
  - En los conjuntos de anuncios activos: C123 Dallas, C128 Odessa, C129 San Antonio–Austin
  - Los leads se venden y nadie devuelve el lead_status, así que hoy Meta optimiza a ciegas
  - Cambiarlo una sola vez y no tocar nada en 3–4 días
- [ ] **Identificar en qué videos sale mi mamá** - for Luis Miguel, due 2026-10-12
  - Ver la vista previa del "Video_V1" en C123 Dallas, C116 Dallas y C93 Dallas
  - Si sale en el V1 (el que trae 377 de 403 leads), no apagarlo hasta tener un reemplazo, y avisarle a Luis Miguel con los números
- [ ] **Apagar creativos sin conversión** - for Luis Miguel, due 2026-10-12
  - Hoy: Imagen V3 en San Antonio–Austin ($39, 1 lead)
  - Video V2 ($16.91/lead), Video V3 ($15.37/lead), Video V4 e Imágenes V1 y V4
  - Regla: gasta $25 sin leads → se apaga; más de $15/lead después de $40 → se apaga
- [ ] **Crear 5 imágenes nuevas con IA** - for Luis Miguel, due 2026-10-14
  - Urgencia con datos reales (verificar la cifra de foster care en el reporte AFCARS), sin cifras inventadas
  - Sin mencionar deportación ni afirmar situaciones personales del usuario ("¿eres inmigrante?")
  - Estilos: tipo noticia, foto emocional, guía en mano, fondo rojo/negro
- [ ] **Grabar o editar 3 versiones del V1 cambiando solo los primeros 3 segundos** - for Luis Miguel, due 2026-10-14
- [ ] **Seguimiento 24 h: prueba del formulario sin preguntas** - for Luis Miguel, due 2026-10-14
  - Anuncio `Video_V1_Custodia_Infantil_NoQuestions_Form` en C123 Dallas: ¿está "Activo" y gastando?
  - ¿Los leads del formulario NoQuestions llegan a la Google Sheet?
  - Solo revisar, no tocar nada
- [ ] **Seguimiento 72 h: comparar formularios** - for Luis Miguel, due 2026-10-16
  - Comparar V1 + formulario viejo vs V1 + NoQuestions: gasto, leads, costo por lead, % que llena (leads ÷ clics)
  - Si el anuncio nuevo gastó menos de $15, Meta no le está dando presupuesto → moverlo a un conjunto aparte
  - Luis Miguel dio libertad sobre los formularios (2026-10-13); vigilar si los compradores reportan más leads malos
  - Incluir los resultados en el reporte del viernes
- [ ] **Decisión del formulario** - for Luis Miguel, due 2026-10-18
  - Gana NoQuestions si: costo por lead menor Y el comprador acepta los leads → usarlo en todas las campañas
  - Pierde si: costo igual o mayor, o el comprador rechaza más leads → apagar y volver al formulario viejo
- [ ] **Lanzar la prueba en C123 Dallas** - for Luis Miguel, due 2026-10-14
  - V1 + versiones nuevas + imágenes IA, con el formulario nuevo
  - Usar "Usar publicación existente" (ID de la publicación del V1) para conservar los likes y comentarios
- [ ] **Revisar la calidad de los 47 leads de C117 Florida_Texas ($1.09/lead)** - for Luis Miguel, due 2026-10-15
  - Si eran buenos, ver qué tenía distinto (formulario, video, segmentación) y replicarlo
- [ ] **Confirmar si somosfamiliaunida.org es de la empresa** - for Luis Miguel, due 2026-10-15
  - Si sí: agregarlo en Administrador de Eventos → Revisar dominios
  - Si no: activar la lista de dominios permitidos en Configuración → Tráfico
- [ ] **Revisión diaria de 10 minutos de las campañas** - recurrente, todos los días
  - Apagar anuncios que gasten $25 sin leads; revisar que todas las integraciones de Sheets estén en verde
  - No hacer cambios grandes en campañas con menos de 3–4 días
- [ ] **Reporte semanal a Luis Miguel** - for Luis Miguel, cada viernes (primero: 2026-10-16)
  - Gasto, leads, costo por lead, qué se apagó, qué se está probando y qué ganó
  - Corto, con números, y una recomendación para la semana siguiente
- [ ] **Crear la plantilla maestra para lanzar campañas por estado** - for Luis Miguel, due 2026-10-19
  - Una campaña por regalo, un conjunto de anuncios por estado (encender/apagar, no crear campañas nuevas)
  - Presupuesto inicial de $50–70/día, formulario ganador, ID de la publicación ganadora, segmentación amplia
  - Checklist: conectar el formulario a la Sheet y enviar un lead de prueba antes de lanzar
- [ ] **Definir las reglas de nombres para el equipo** - for Luis Miguel, due 2026-10-19
  - Anuncios descriptivos: Custodia_Mama_PreguntaJuez_Formulario1 (nunca "V1" a secas)
  - Nunca renombrar ni borrar una pestaña conectada a Meta

## Waiting On

- [ ] **Luis Miguel: detalles de la campaña de GPS** - since 2026-10-12
  - Qué es (GPS para niños, guía o producto), para qué cliente, estados y precio de venta del lead
- [ ] **Luis Miguel: precio de venta del lead** - since 2026-10-12
  - Para calcular el costo por lead máximo rentable (hoy pierde $1–2 por lead)
- [ ] **Luis Miguel / compradores: aviso de leads válidos o inválidos** - since 2026-10-12
  - Si el comprador dice cuáles leads no sirvieron, se puede marcar en la Sheet para que Meta aprenda

## Someday

- [ ] **Preparar la campaña de GPS** - for Luis Miguel, después de recibir los detalles
- [ ] **Auditar el portafolio de reclutamiento** - pendiente la descarga de Meta
- [ ] **Usar el feedback de calidad del comprador como señal para Meta** - si llegan datos de válido/inválido

## Done

- [x] ~~**Formulario sin preguntas publicado** (`Form_ENM_Custodia_Infantil_More_Volume_Autofill_On_NoQuestions_Dallas`, anuncio V1 duplicado en C123 Dallas)~~ (2026-10-13)

- [x] ~~**Google Sheet nueva conectada a los formularios activos** (formularios duplicados → LM - Main Pixel; la integración vieja con error se deja, no recibe nada)~~ (2026-10-13)
