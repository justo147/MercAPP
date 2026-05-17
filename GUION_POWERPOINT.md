# MercApp — Guión del PowerPoint
## Presentación completa con lo que decir en cada diapositiva

> Duración estimada: 15-20 minutos + demo + preguntas
> Reparto sugerido: 4 personas → cada una lleva 2-3 diapositivas + parte de la demo

---

## DIAPOSITIVA 1 — PORTADA

**Contenido visual:**
- Logo/nombre MercApp en grande
- Subtítulo: "Marketplace de segunda mano con trueque integrado"
- IES Isidro de Arcenegui y Carmona · 2ºDAW 2025/26
- Nombres del equipo
- Fondo limpio, colores de la app

**Lo que se dice:**
> "Buenos días / tardes. Somos [nombres] y vamos a presentaros MercApp, una plataforma web de compraventa de segunda mano que hemos desarrollado desde cero durante este curso. Lo que hace diferente a MercApp de otros marketplaces es que integra el trueque como funcionalidad nativa, algo que plataformas como Wallapop o Vinted no ofrecen."

---

## DIAPOSITIVA 2 — ¿QUÉ ES MERCAPP? (Problema y solución)

**Contenido visual:**
- Dos columnas: PROBLEMA | SOLUCIÓN
- Problema: "Las plataformas actuales solo permiten comprar y vender con dinero"
- Solución: "MercApp permite vender, intercambiar y combinar producto + dinero"
- Logos pequeños de Wallapop, Vinted, Milanuncios tachados con "Sin trueque"
- Flecha hacia MercApp con "✓ Trueque integrado"

**Lo que se dice:**
> "El problema que identificamos es que las plataformas de segunda mano existentes, como Wallapop o Vinted, están pensadas exclusivamente para transacciones monetarias. Si tienes un producto que quieres intercambiar directamente por otro, no puedes hacerlo de forma nativa."
>
> "MercApp resuelve esto con tres tipos de transacción: venta directa, intercambio puro entre productos, y una modalidad mixta donde combinas un producto con una compensación económica."

---

## DIAPOSITIVA 3 — ANÁLISIS DE MERCADO (Competidores)

**Contenido visual:**
- Tabla comparativa con 4 competidores
- Columnas: Chat, Reputación, Pagos integrados, Soporte trueque
- Filas: Wallapop, Vinted, Milanuncios, eBay, **MercApp** (destacada en verde)
- Todas las filas en "Soporte trueque" tienen ✗ excepto MercApp que tiene ✓

**Lo que se dice:**
> "Hicimos un análisis de las principales plataformas del mercado. Wallapop lidera en España con geolocalización y chat. Vinted domina en moda con envíos integrados. eBay tiene alcance internacional. Pero ninguna de ellas soporta el intercambio de productos de forma nativa."
>
> "MercApp ocupa ese hueco. Además de chat y valoraciones, ofrece los tres tipos de transacción y un sistema de reputación más detallado con tres dimensiones de valoración."

---

## DIAPOSITIVA 4 — TECNOLOGÍAS UTILIZADAS

**Contenido visual:**
- Iconos/logos de cada tecnología en grid:
  - Backend: PHP 8, MySQL
  - Frontend: Bootstrap 5.3.3, Twig, JavaScript
  - Herramientas: PHPMailer, Stripe, Nominatim/OpenStreetMap
  - Dev: XAMPP, Git/GitHub, Composer, PHPUnit
- Sin texto largo, solo logos y nombres

**Lo que se dice:**
> "El stack tecnológico está basado completamente en código abierto. En el backend usamos PHP 8 puro con arquitectura MVC, sin frameworks, y MySQL como base de datos."
>
> "En el frontend, Bootstrap 5 para el diseño responsive y Twig como motor de plantillas, que nos permite separar limpiamente el HTML de la lógica PHP."
>
> "Para funcionalidades extra: PHPMailer con SMTP de Gmail para correos transaccionales, Stripe para pagos con tarjeta, y Nominatim de OpenStreetMap para la geocodificación de direcciones, completamente gratuita."

---

## DIAPOSITIVA 5 — ARQUITECTURA MVC

**Contenido visual:**
- Diagrama de tres capas vertical:
  ```
  [VISTA — Templates Twig]
         ↕
  [CONTROLADOR — PHP handlers]
         ↕
  [MODELO — Clases PHP + PDO]
         ↕
  [BASE DE DATOS — MySQL]
  ```
- A la derecha: estructura de carpetas simplificada
  - `templates/` → Vistas
  - `controllers/` → Controladores
  - `models/` → Modelos
  - `api/` → Endpoints JSON

**Lo que se dice:**
> "La arquitectura sigue el patrón MVC. Las vistas son plantillas Twig en la carpeta templates. Los controladores son scripts PHP que procesan los formularios y llaman a los modelos. Los modelos encapsulan toda la lógica de negocio y el acceso a la base de datos mediante PDO."
>
> "Además, tenemos una capa de API REST en la carpeta api/, con endpoints JSON que el frontend consume mediante fetch para funcionalidades como el chat en tiempo real o la búsqueda con filtros."

---

## DIAPOSITIVA 6 — BASE DE DATOS (Esquema)

**Contenido visual:**
- Diagrama ER simplificado con las tablas principales y sus relaciones
- Tablas: Usuarios, Productos, Chats, Mensajes, Transacciones, Intercambio_Detalle, Valoraciones
- Resaltar la relación Chat → Transacción → Intercambio_Detalle
- Nota: "13 tablas en total"

**Lo que se dice:**
> "La base de datos tiene 13 tablas. Las más importantes son: Usuarios y Productos, que son el núcleo. Los Chats conectan a un comprador con un vendedor sobre un producto concreto. Dentro del chat puede existir una Transacción, que lleva todo el estado del acuerdo."
>
> "Una tabla interesante es Intercambio_Detalle, que almacena el producto que ofrece el comprador en las transacciones de trueque. Y la tabla Valoraciones guarda las puntuaciones en tres dimensiones: fiabilidad, comunicación y puntualidad."

---

## DIAPOSITIVA 7 — FUNCIONALIDADES PRINCIPALES

**Contenido visual:**
- Grid de 6 tarjetas con icono + nombre:
  1. 🔐 Registro y verificación por email
  2. 📦 Publicación de productos con imágenes WebP
  3. 🔍 Búsqueda con filtros y proximidad geográfica
  4. 💬 Chat en tiempo real (polling)
  5. 🔄 Transacciones: venta, trueque, mixto
  6. 💳 Pago con Stripe
  7. ⭐ Valoraciones multidimensionales
  8. 🛡️ Panel de administración
  9. 🌙 Modo oscuro

**Lo que se dice:**
> "Las funcionalidades implementadas cubren todo el ciclo de vida de una transacción. Desde el registro con verificación por email, pasando por la publicación de productos con conversión automática a WebP, hasta la búsqueda por proximidad usando la fórmula Haversine con coordenadas GPS."
>
> "El chat usa polling cada 3 segundos, lo que permite una experiencia fluida sin necesitar WebSockets. Las transacciones pasan por 6 estados y hay integración real con Stripe para pagos con tarjeta."

---

## DIAPOSITIVA 8 — MÁQUINA DE ESTADOS DE TRANSACCIONES

**Contenido visual:**
- Diagrama de flujo horizontal o vertical:
  ```
  [pendiente] → [aceptada] → [pago_pendiente] → [enviado] → [entregado ✓]
      ↓              ↓              ↓               ↓
  [cancelada ✗] ←─────────────────────────────────────
  ```
- Debajo de cada flecha: quién realiza la acción (Comprador / Vendedor)
- Colores: azul=activo, verde=completado, rojo=cancelado

**Lo que se dice:**
> "Uno de los elementos más complejos del proyecto es la máquina de estados de las transacciones. Cada transacción pasa por 6 estados posibles."
>
> "El comprador es quien acepta e informa del pago. El vendedor confirma el envío y añade el número de seguimiento. El comprador confirma la recepción. En cualquier momento, cualquiera de los dos puede cancelar."
>
> "Hay un flujo especial con Stripe: si el comprador paga con tarjeta, la transacción salta directamente de pendiente a pago_pendiente, porque el pago ya está verificado en el servidor."

---

## DIAPOSITIVA 9 — PAGO CON STRIPE

**Contenido visual:**
- Diagrama de secuencia simplificado:
  ```
  Comprador → Frontend: selecciona tarjeta
  Frontend → Stripe.js: monta CardElement
  Frontend → Backend: POST /api/stripe_create_payment.php
  Backend → Stripe API: crea PaymentIntent
  Stripe API → Backend: devuelve client_secret
  Backend → Frontend: client_secret
  Frontend → Stripe.js: confirmCardPayment()
  Stripe → Frontend: pago succeeded
  Frontend → Backend: envía stripe_payment_intent_id
  Backend → Stripe API: verifica PaymentIntent
  Backend: avanza transacción a pago_pendiente
  ```
- Icono de Stripe + candado de seguridad

**Lo que se dice:**
> "Para los pagos con tarjeta hemos integrado Stripe. El flujo tiene dos partes clave: el frontend y el backend."
>
> "En el frontend, Stripe.js crea un campo de tarjeta seguro que nunca pasa por nuestros servidores. El backend crea un PaymentIntent con el importe exacto del producto. Cuando el pago se confirma en el cliente, el backend lo verifica de forma independiente con la API de Stripe antes de avanzar el estado de la transacción. Esto evita cualquier manipulación del lado cliente."

---

## DIAPOSITIVA 10 — CHAT Y TRANSACCIONES (Interfaz)

**Contenido visual:**
- Captura de pantalla real del chat de la aplicación
- Señalar con flechas las dos zonas:
  - Izquierda: hilo de mensajes
  - Derecha: panel de transacción con timeline vertical
- Zoom en el timeline mostrando los estados

**Lo que se dice:**
> "La vista del chat tiene un diseño de dos columnas. A la izquierda el hilo de mensajes, donde los mensajes del sistema aparecen centrados para distinguirlos de los mensajes humanos. A la derecha, un panel sticky con el timeline vertical de la transacción."
>
> "El panel es contextual: solo muestra la acción que corresponde al estado actual y al rol del usuario. Si eres el comprador en estado 'enviado', ves el botón de confirmar entrega. Si eres el vendedor, ves el estado pero no puedes hacer esa acción."

---

## DIAPOSITIVA 11 — SEGURIDAD

**Contenido visual:**
- Lista con iconos de escudo:
  - 🛡️ Prepared statements PDO → previene SQL Injection
  - 🛡️ Twig escape automático → previene XSS
  - 🛡️ bcrypt (password_hash) → contraseñas seguras
  - 🛡️ Rate limiting (LoginIntentos) → previene fuerza bruta
  - 🛡️ Tokens con caducidad → verificación y recuperación segura
  - 🛡️ Variables de entorno (.env) → credenciales fuera del código

**Lo que se dice:**
> "La seguridad no fue un añadido final, sino parte del diseño desde el principio."
>
> "Usamos prepared statements en absolutamente todas las consultas a la base de datos, sin excepción. Twig aplica escape HTML automáticamente en todas las variables de las plantillas. Las contraseñas se hashean con bcrypt, que incluye sal aleatoria automática."
>
> "El rate limiting de login evita ataques de fuerza bruta registrando los intentos fallidos por IP y email. Y todas las credenciales sensibles, como las claves de Stripe o el SMTP de Gmail, están en un archivo .env que nunca se sube al repositorio."

---

## DIAPOSITIVA 12 — TESTING

**Contenido visual:**
- Captura del output de PHPUnit con --testdox
- Destacar el número de tests y que todos pasan (verde)
- Mencionar: PHPUnit 11.x, tests unitarios + integración

**Lo que se dice:**
> "Para garantizar la calidad del código, usamos PHPUnit 11. Los tests cubren principalmente el modelo de transacciones, que es la parte más crítica de la lógica de negocio."
>
> "Los tests unitarios verifican que las transiciones de estado son correctas, que solo los actores autorizados pueden hacer cada cambio, y que los datos se guardan correctamente en la base de datos."

---

## DIAPOSITIVA 13 — DEMO EN VIVO

**Contenido visual:**
- Diapositiva simple: "DEMO EN VIVO" con el flujo que vais a mostrar
- Lista del flujo de la demo:
  1. Registrar usuario y verificar email
  2. Publicar un producto con imágenes
  3. Buscar con filtros y proximidad
  4. Iniciar chat y proponer transacción
  5. Aceptar transacción (elegir método de pago)
  6. Pagar con Stripe (tarjeta de test)
  7. Vendedor marca como enviado
  8. Comprador confirma entrega + valoración
  9. Ver panel de admin

**Lo que se dice:**
> "Vamos a hacer una demostración en vivo del flujo completo de una transacción. Tenemos dos usuarios ya preparados: uno como vendedor y otro como comprador."

*(Cambiar a la aplicación y hacer la demo)*

**Puntos clave a mostrar durante la demo:**
- El modo oscuro (hacer el cambio en vivo)
- El autocomplete de dirección con Nominatim
- La búsqueda por proximidad con mapa
- El formulario de aceptación con las opciones de pago
- El campo de tarjeta de Stripe (bien integrado, no es un iframe feo)
- El timeline vertical cambiando de estado
- La valoración multidimensional
- El panel de admin con los datos reales

---

## DIAPOSITIVA 14 — DIFICULTADES Y APRENDIZAJES

**Contenido visual:**
- Dos columnas: DIFICULTADES | LO QUE APRENDIMOS
- 3-4 puntos en cada columna

**Sugerencias de contenido:**
- Dificultad: implementar la máquina de estados con validaciones de rol
- Dificultad: integrar Stripe de forma segura (verificación server-side)
- Dificultad: el chat en tiempo real sin WebSockets
- Dificultad: la conversión de imágenes a WebP
- Aprendizaje: arquitectura MVC desde cero sin framework
- Aprendizaje: seguridad web (SQL injection, XSS, bcrypt)
- Aprendizaje: trabajar con APIs externas (Stripe, Nominatim)
- Aprendizaje: trabajo en equipo con Git y resolución de conflictos

**Lo que se dice:**
> "El mayor reto técnico fue diseñar la máquina de estados de las transacciones. Tuvimos que asegurarnos de que cada transición fuera válida, que el actor correcto la ejecutara, y que los datos extra se guardaran en el momento apropiado."
>
> "La integración con Stripe nos enseñó algo importante sobre seguridad: nunca confiar solo en el cliente. Aunque el navegador diga que el pago fue correcto, el servidor debe verificarlo de forma independiente con la API de Stripe."

---

## DIAPOSITIVA 15 — LÍNEAS DE MEJORA FUTURAS

**Contenido visual:**
- Lista de 4-5 mejoras futuras con iconos
- 🤖 Chat con IA para búsqueda de productos
- 🗺️ Mapa interactivo en la búsqueda
- 📱 App móvil nativa
- 🔔 Notificaciones push (no solo en la app)
- 🤝 Matchmaking automático de intercambios

**Lo que se dice:**
> "Si bien el proyecto está completo en sus funcionalidades core, identificamos varias líneas de mejora. La más interesante sería un asistente de búsqueda con IA que ayude al usuario a encontrar productos mediante lenguaje natural."
>
> "También un sistema de matchmaking que cruce automáticamente lo que busca un usuario con lo que otros tienen para intercambiar."

---

## DIAPOSITIVA 16 — CIERRE

**Contenido visual:**
- Logo MercApp centrado
- URL o QR del repositorio GitHub
- "¿Preguntas?"
- Nombres del equipo

**Lo que se dice:**
> "MercApp es una aplicación funcional y completa que demuestra que es posible construir un marketplace con funcionalidades avanzadas usando PHP nativo, sin depender de grandes frameworks."
>
> "El código está disponible en GitHub. Quedamos a vuestra disposición para cualquier pregunta."

---

## CONSEJOS PARA LA PRESENTACIÓN

### Reparto sugerido (4 personas, ~20 min)

| Persona | Diapositivas | Tiempo |
|---------|-------------|--------|
| Persona 1 | 1-4 (intro, mercado, tecnologías) | 4 min |
| Persona 2 | 5-8 (arquitectura, BD, funcionalidades, estados) | 4 min |
| Persona 3 | 9-12 (Stripe, chat, seguridad, tests) | 4 min |
| Persona 4 | 13-16 (demo, dificultades, cierre) + lleva el ratón | 8 min |

### Durante la demo

- Tener dos pestañas abiertas: una con usuario comprador, otra con vendedor (navegador diferente o modo incógnito)
- Tener productos ya publicados para no perder tiempo
- La tarjeta de Stripe de test es: `4242 4242 4242 4242`, fecha cualquiera futura, CVC cualquiera
- Si algo falla, no entrar en pánico — explicar qué debería pasar y seguir

### Si os preguntan algo que no sabéis

> "Es una buena pregunta, no lo hemos implementado en esta versión pero sería interesante como mejora futura."

o

> "Eso está gestionado en el modelo Transaction.php / en el controlador / en la API..."

### Palabras clave a usar (suenan bien al tribunal)

- "Prepared statements" (al hablar de seguridad)
- "Máquina de estados" (para las transacciones)
- "Polling HTTP" (para el chat)
- "PaymentIntent" (para Stripe)
- "Haversine" (para la búsqueda por proximidad)
- "Bcrypt / password_hash" (para contraseñas)
- "Motor de plantillas Twig" (para las vistas)
- "Arquitectura MVC" (para la estructura)
- "Idempotente" (para las migraciones SQL)
