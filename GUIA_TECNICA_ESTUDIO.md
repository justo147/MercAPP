# MercApp — Guía técnica de estudio para la exposición

> Documento interno para que el equipo pueda responder preguntas técnicas sobre cómo está construida la aplicación.

---

## ÍNDICE

1. [Arquitectura general](#1-arquitectura-general)
2. [Base de datos](#2-base-de-datos)
3. [Autenticación y sesiones](#3-autenticación-y-sesiones)
4. [Sistema de productos](#4-sistema-de-productos)
5. [Sistema de chat](#5-sistema-de-chat)
6. [Máquina de estados de transacciones](#6-máquina-de-estados-de-transacciones)
7. [Integración con Stripe](#7-integración-con-stripe)
8. [Geocodificación con Nominatim](#8-geocodificación-con-nominatim)
9. [Sistema de valoraciones](#9-sistema-de-valoraciones)
10. [Panel de administración](#10-panel-de-administración)
11. [Motor de plantillas Twig](#11-motor-de-plantillas-twig)
12. [Seguridad](#12-seguridad)
13. [Tests con PHPUnit](#13-tests-con-phpunit)
14. [API REST — endpoints](#14-api-rest--endpoints)
15. [Preguntas frecuentes del tribunal](#15-preguntas-frecuentes-del-tribunal)

---

## 1. ARQUITECTURA GENERAL

### ¿Qué patrón usa MercApp?

MercApp usa **MVC ligero sin frameworks**. Esto significa:

- **M (Modelo)**: clases PHP en `models/` que acceden a la BD mediante PDO
- **V (Vista)**: plantillas Twig en `templates/` (.html.twig)
- **C (Controlador)**: scripts PHP en `controllers/` que procesan formularios

No usamos Laravel, Symfony ni ningún framework. Todo es PHP nativo.

### Flujo de una petición típica

```
Navegador
  → Apache (XAMPP)
    → PHP (ej: controllers/handlers/upload_product_handler.php)
      → Modelo (ej: models/Product.php)
        → MySQL (PDO)
      ← datos
    ← lógica de negocio
  → Twig renderiza la plantilla
← HTML al navegador
```

### Estructura de carpetas clave

```
MercApp/
├── api/                   → Endpoints JSON (fetch desde JS)
├── config/
│   ├── bootstrap.php      → Carga .env, arranca Twig, define $BASE
│   ├── db.php             → Clase Database, devuelve PDO
│   └── mail_config.php    → PHPMailer configurado
├── controllers/
│   ├── handlers/          → Un archivo por formulario
│   ├── chat_start_transaction.php
│   └── chat_update_transaction.php
├── models/                → Clases con la lógica de negocio
├── templates/             → Vistas Twig (.html.twig)
├── public/js/             → JavaScript del frontend
├── migrations/            → SQL para actualizar la BD
├── tests/                 → PHPUnit
├── bd.sql                 → Esquema completo de la BD
└── .env                   → Variables de entorno (no en git)
```

### ¿Cómo se conecta todo?

Cada página PHP hace:
```php
require_once 'config/bootstrap.php';  // carga .env, Twig, sesión
$conn = Database::getConnection();    // PDO a MySQL
$model = new Product($conn);          // instancia el modelo
echo $twig->render('home.html.twig', ['productos' => $model->getAll()]);
```

---

## 2. BASE DE DATOS

### Motor y nombre

- **Motor**: MySQL
- **BD**: `mercapp`
- **Usuario**: `root` (sin contraseña, XAMPP local)
- **Acceso PHP**: PDO con prepared statements siempre

### Tablas principales y para qué sirven

| Tabla | Para qué |
|-------|---------|
| `Usuarios` | Registro, login, perfil |
| `Productos` | Anuncios publicados |
| `Imagenes_Producto` | Fotos de cada producto (1 a N) |
| `Categorias` | Electrónica, Ropa, Libros… |
| `EstadosProducto` | Nuevo, Como nuevo, Bueno, Regular |
| `Chats` | Sala de conversación entre comprador y vendedor |
| `Mensajes` | Mensajes individuales de un chat |
| `Transacciones` | El acuerdo comercial completo |
| `Intercambio_Detalle` | Producto que ofrece el comprador en un trueque |
| `Valoraciones` | Puntuaciones tras completar una transacción |
| `Notificaciones` | Alertas para cada usuario |
| `Reportes` | Denuncias de productos |
| `LoginIntentos` | Rate limiting del login |

### Relaciones clave (explicadas sin diagrama)

- Un **Usuario** puede tener muchos **Productos**
- Un **Producto** tiene muchas **Imagenes_Producto** (una es la portada, orden=1)
- Un **Chat** conecta un Producto + un Comprador + un Vendedor
- Dentro de un Chat hay muchos **Mensajes** (usuario_id puede ser NULL para mensajes del sistema)
- Un Chat puede tener una **Transacción** activa
- Una Transacción puede tener un **Intercambio_Detalle** (si es trueque)
- Una Transacción completada genera dos **Valoraciones** (cada parte valora a la otra)

### Campos importantes de Transacciones

```sql
estado          ENUM('pendiente','aceptada','pago_pendiente','enviado','entregado','cancelada')
tipo            ENUM('venta','intercambio','mixto')
metodo_pago     ENUM('efectivo','transferencia','bizum','paypal','stripe','otro')
precio_final    DECIMAL(10,2)       -- snapshot del precio al crear la transacción
dinero_extra    DECIMAL(10,2)       -- compensación en transacciones mixtas
direccion_envio VARCHAR(300)
numero_seguimiento VARCHAR(100)
stripe_payment_intent_id VARCHAR(100)
fecha_aceptacion, fecha_pago_confirmado, fecha_envio, fecha_entrega  -- DATETIME
```

### ¿Por qué usuario_id es NULL en Mensajes?

Los mensajes automáticos del sistema (ej: "La transacción ha sido aceptada") no tienen usuario real. La FK `usuario_id` en `Mensajes` está declarada como **nullable** para permitir esto sin violar integridad referencial.

---

## 3. AUTENTICACIÓN Y SESIONES

### Registro de usuario

1. Formulario en `templates/register.html.twig`
2. Handler: `controllers/handlers/register_handler.php`
3. Lo que hace:
   - Valida email único en BD
   - Hashea la contraseña: `password_hash($pass, PASSWORD_DEFAULT)` → bcrypt
   - Genera token aleatorio: `bin2hex(random_bytes(32))`
   - Inserta usuario con `email_verificado = 0`
   - Envía email con enlace de verificación vía PHPMailer

### Verificación de email

- El enlace lleva a `controllers/handlers/verify_handler.php?token=XXX`
- Busca el token en BD, comprueba que no haya expirado
- Actualiza `email_verificado = 1`
- Hasta verificar, el usuario es redirigido a `templates/pending.html.twig`

### Login

1. `controllers/handlers/login_handler.php`
2. Comprueba rate limiting (tabla `LoginIntentos`)
3. Busca usuario por email
4. `password_verify($inputPass, $hashBD)` → bcrypt verifica
5. Si correcto, guarda en sesión:

```php
$_SESSION['user_id']      = $user['id'];
$_SESSION['email']        = $user['email'];
$_SESSION['name']         = $user['nombre'];
$_SESSION['profile_photo']= $user['foto_perfil'];
$_SESSION['role']         = $user['rol']; // 'registrado' | 'admin'
```

### Rate limiting de login

Tabla `LoginIntentos` registra email + IP + timestamp de cada intento fallido. Si hay más de N intentos en los últimos X minutos, bloquea el acceso temporalmente.

### Recuperación de contraseña

1. Usuario introduce email en `templates/forgot.html.twig`
2. Se genera token con caducidad de 1 hora
3. Email enviado con enlace a `templates/reset.html.twig`
4. Al confirmar nueva contraseña: `password_hash()` y se invalida el token

---

## 4. SISTEMA DE PRODUCTOS

### Publicar un producto

- Vista: `templates/upload_product.html.twig`
- Handler: `controllers/handlers/upload_product_handler.php`
- Modelo: `models/Product.php`

### Lo que ocurre al publicar

1. Recibe datos del formulario (título, descripción, precio, categoría, tipo de transacción)
2. Recibe las imágenes, las convierte a **WebP** con GD:
   ```php
   $img = imagecreatefromjpeg($tmp);
   imagewebp($img, $destino, 85); // calidad 85%
   ```
3. Guarda coordenadas lat/lon si el usuario usó el autocomplete de dirección
4. Inserta en `Productos` + inserta cada imagen en `Imagenes_Producto`

### Estados de un producto

- `activo` → visible en el marketplace
- `pausado` → oculto temporalmente por el dueño
- `vendido` → ya no disponible

### Tipos de transacción

- `venta` → solo dinero
- `intercambio` → solo trueque (el comprador ofrece otro producto)
- `mixto` → producto + dinero extra

### Búsqueda con filtros

Endpoint: `api/search_products.php`

Parámetros aceptados:
- `q` → texto libre (busca en título y descripción)
- `categoria` → ID de categoría
- `lat` + `lon` + `distancia_km` → filtro geográfico por proximidad
- `orden` → precio_asc, precio_desc, fecha_desc
- `tipo_transaccion` → venta/intercambio/mixto
- `precio_min` + `precio_max`

La búsqueda por proximidad usa la **fórmula Haversine** en SQL:
```sql
(6371 * acos(cos(radians(?)) * cos(radians(lat)) *
cos(radians(lon) - radians(?)) +
sin(radians(?)) * sin(radians(lat)))) AS distancia
HAVING distancia < ?
```
Esto calcula la distancia en km entre las coordenadas del usuario y las del producto.

---

## 5. SISTEMA DE CHAT

### ¿Cómo funciona el chat?

El chat usa **polling HTTP** (no WebSockets). Cada 3 segundos el navegador pregunta al servidor si hay mensajes nuevos.

```javascript
// public/js/chat.js (simplificado)
setInterval(() => {
    fetch(`/api/chat_messages.php?chat_id=${chatId}&after_id=${lastId}`)
        .then(r => r.json())
        .then(data => {
            data.messages.forEach(msg => appendMessage(msg));
            if (data.messages.length > 0) lastId = data.messages.at(-1).id;
        });
}, 3000);
```

### Estructura de un chat

- Un chat está vinculado a un **producto** (no a una transacción)
- Si hay una nueva transacción sobre el mismo producto, se reutiliza el mismo chat
- El chat se **bloquea** (no se puede enviar) cuando la transacción está en `entregado` o `cancelada`

### Mensajes del sistema

Cuando cambia el estado de la transacción, se inserta automáticamente un mensaje con `usuario_id = NULL`:
```php
// En chat_update_transaction.php
$chat->sendSystemMessage($chatId, "✅ La transacción ha sido aceptada.");
```
Estos mensajes se muestran centrados con estilo diferente en la vista.

### Layout del chat (dos columnas)

```
┌─────────────────────┬──────────────────────┐
│  CHAT (col-lg-7)    │  TRANSACCIÓN (col-lg-5│
│                     │  sticky)              │
│  [mensajes...]      │  [timeline vertical]  │
│                     │  [resumen producto]   │
│  [input enviar]     │  [acción contextual]  │
└─────────────────────┴──────────────────────┘
```

En móvil se apilan verticalmente.

### Conteo de no leídos

`api/chat_unread_count.php` devuelve el total de mensajes no leídos del usuario en sesión. El navbar lo consulta periódicamente y muestra un badge rojo.

---

## 6. MÁQUINA DE ESTADOS DE TRANSACCIONES

### Los 6 estados

```
pendiente
  ↓ (comprador acepta)
aceptada
  ↓ (comprador informa que pagó)
pago_pendiente
  ↓ (vendedor prepara envío)
enviado
  ↓ (comprador confirma recepción)
entregado ✓

cualquier estado → cancelada ✗
```

### ¿Quién puede hacer cada transición?

| Transición | Actor |
|-----------|-------|
| pendiente → aceptada | **Comprador** |
| aceptada → pago_pendiente | **Comprador** |
| pago_pendiente → enviado | **Vendedor** |
| enviado → entregado | **Comprador** |
| * → cancelada | **Cualquiera** |

### ¿Dónde se gestiona esto en el código?

En `models/Transaction.php` existe el método `updateEstado()`:

```php
public function updateEstado($transaccionId, $nuevoEstado, $userId, $esVendedor) {
    // 1. Carga la transacción actual
    // 2. Valida que la transición sea válida según el rol
    // 3. Actualiza el estado en BD
    // 4. Guarda el timestamp correspondiente (fecha_aceptacion, etc.)
    // 5. Si es 'cancelada', pone el producto de vuelta a 'activo'
    // 6. Si es 'entregado', envía email a ambas partes
}
```

El controlador `controllers/chat_update_transaction.php` llama a este método tras validar que el usuario en sesión tiene permiso.

### Datos extra en cada transición

Al **aceptar** (pendiente → aceptada), el comprador aporta:
- `metodo_pago` (efectivo, bizum, stripe…)
- `direccion_envio` (normalizada con Nominatim)
- `notas_comprador`
- Si es intercambio: el `producto_ofrecido_id` → se guarda en `Intercambio_Detalle`

Al **enviar** (pago_pendiente → enviado), el vendedor aporta:
- `numero_seguimiento` (opcional)

### Bloqueo del producto

Cuando una transacción está en `pendiente`, `aceptada` o `enviado`, el producto queda **bloqueado**:
- No se puede editar
- No se puede eliminar
- No se puede iniciar otra transacción sobre él

Al llegar a `entregado` o `cancelada`, el producto vuelve a `activo`.

---

## 7. INTEGRACIÓN CON STRIPE

### Flujo completo paso a paso

1. El comprador selecciona **"Tarjeta · Stripe"** como método de pago
2. El frontend carga `Stripe.js v3` desde los CDN de Stripe y monta el `CardElement` (campo de tarjeta)
3. Al enviar el formulario, el JS llama a:
   ```
   POST /api/stripe_create_payment.php  { transaccion_id: 42 }
   ```
4. El servidor PHP crea un **PaymentIntent**:
   ```php
   \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
   $intent = \Stripe\PaymentIntent::create([
       'amount'   => $precioEnCentimos,  // ej: 5000 = 50,00€
       'currency' => 'eur',
   ]);
   return json_encode(['client_secret' => $intent->client_secret]);
   ```
5. El JS confirma el pago con la tarjeta:
   ```javascript
   const result = await stripe.confirmCardPayment(clientSecret, {
       payment_method: { card: cardElement }
   });
   if (result.paymentIntent.status === 'succeeded') {
       // envía el formulario con stripe_payment_intent_id
   }
   ```
6. El controlador verifica el PaymentIntent en el servidor (nunca confiar solo en el cliente):
   ```php
   $intent = \Stripe\PaymentIntent::retrieve($intentId);
   if ($intent->status === 'succeeded') {
       // avanza la transacción a pago_pendiente
   }
   ```

### Diferencia con el flujo normal

El flujo Stripe **salta el estado `aceptada`** y va directamente de `pendiente` a `pago_pendiente`. El PaymentIntent actúa como prueba de pago.

### Claves de Stripe

Están en `.env`:
```
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```
En modo test, se puede pagar con la tarjeta `4242 4242 4242 4242`.

---

## 8. GEOCODIFICACIÓN CON NOMINATIM

### ¿Qué es Nominatim?

Es la API de geocodificación de **OpenStreetMap**. Convierte texto de dirección en coordenadas (lat, lon). Es **gratuita y sin API key**.

### Arquitectura del proxy

No llamamos a Nominatim directamente desde el navegador (por política de uso y CORS). Usamos un **proxy PHP**:

```
Navegador → api/normalize_address.php → nominatim.openstreetmap.org
```

El proxy añade el `User-Agent` requerido y devuelve el JSON al navegador.

### Código del proxy (simplificado)

```php
// api/normalize_address.php
$q = urlencode($_GET['q']);
$url = "https://nominatim.openstreetmap.org/search?q={$q}&format=json&countrycodes=es&limit=5";
$ctx = stream_context_create(['http' => ['header' => 'User-Agent: MercApp/1.0']]);
$resultado = file_get_contents($url, false, $ctx);
header('Content-Type: application/json');
echo $resultado;
```

### Autocomplete en el frontend

`public/js/address_autocomplete.js` es un componente reutilizable:
- Escucha el evento `input` en el campo de dirección
- A partir de 3 caracteres, llama al proxy con debounce
- Muestra un dropdown con las sugerencias
- Al seleccionar, rellena el campo de texto + campos ocultos `lat` e `ion`

Se usa en:
- Formulario de publicar producto (campo `ubicacion`)
- Formulario de aceptar transacción (campo `direccion_envio`)

---

## 9. SISTEMA DE VALORACIONES

### ¿Cuándo se puede valorar?

Solo cuando la transacción está en estado **`entregado`**. Si intentas valorar sin esa condición, el sistema lo rechaza (validación en backend).

### ¿Qué se valora?

Tres dimensiones, de 1 a 5 estrellas:
- **Fiabilidad** — ¿cumplió lo que prometió?
- **Comunicación** — ¿respondió rápido y con claridad?
- **Puntualidad** — ¿fue puntual en la entrega/recogida?

### Flujo

1. Al llegar a `entregado`, aparece un modal de valoración automáticamente (con 800ms de delay)
2. El usuario envía la valoración → `controllers/handlers/rating_handler.php`
3. Se guarda en `Valoraciones`: `evaluador_id`, `evaluado_id`, `fiabilidad`, `comunicacion`, `puntualidad`, `comentario`
4. El perfil público de cada usuario muestra su **media** calculada con `api/get_rating.php`

### ¿Cómo se calcula la media?

```sql
SELECT AVG((fiabilidad + comunicacion + puntualidad) / 3) AS media
FROM Valoraciones
WHERE evaluado_id = ?
```

---

## 10. PANEL DE ADMINISTRACIÓN

### Acceso

Solo usuarios con `$_SESSION['role'] === 'admin'`. Cualquier endpoint admin verifica esto al inicio:
```php
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Acceso denegado']));
}
```

### Funcionalidades

| Sección | Endpoint | Qué hace |
|---------|---------|---------|
| Usuarios | `api/admin_users.php` | Lista paginada, búsqueda por nombre/email, cambio de rol, bloqueo |
| Productos | `api/admin_products.php` | Lista paginada, filtro por estado, eliminar |
| Reportes | `api/admin_reports.php` | Lista de denuncias, marcar como revisado/descartado |
| Estadísticas | Panel principal | Totales: usuarios, productos activos, transacciones completadas |
| Export usuarios | `api/admin_export_usuarios.php` | Descarga CSV |
| Export transacciones | `api/admin_export_transacciones.php` | Descarga CSV |

### ¿Cómo funciona la exportación CSV?

```php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="usuarios.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Nombre', 'Email', 'Rol', 'Fecha registro']);
while ($row = $stmt->fetch()) {
    fputcsv($out, [$row['id'], $row['nombre'], $row['email'], ...]);
}
```

---

## 11. MOTOR DE PLANTILLAS TWIG

### ¿Qué es Twig?

Twig es un motor de plantillas para PHP. En vez de mezclar HTML y PHP (`<?php echo $var ?>`), usamos una sintaxis limpia:

```twig
{{ variable }}              → imprimir variable (con escape automático)
{% if condicion %}          → condicional
{% for item in lista %}     → bucle
{% extends 'base.html.twig' %}  → herencia de plantilla
{% block contenido %}       → zona que puede sobrescribir la plantilla hija
```

### Herencia de plantillas

`base.html.twig` define la estructura HTML completa con navbar, footer y bloques vacíos. Cada vista hereda de ella:

```twig
{# templates/home.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Inicio — MercApp{% endblock %}

{% block content %}
  <h1>Bienvenido {{ usuario.nombre }}</h1>
{% endblock %}
```

### Seguridad en Twig

Por defecto Twig aplica `htmlspecialchars()` a todas las variables automáticamente, evitando XSS. Para mostrar HTML sin escapar se usa `{{ variable|raw }}` (solo cuando el HTML es de confianza).

### Configuración de Twig

En `config/twig.php`:
```php
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'cache' => __DIR__ . '/../cache',  // caché de plantillas compiladas
    'auto_reload' => true,
]);
```

---

## 12. SEGURIDAD

### Medidas implementadas

| Amenaza | Medida |
|---------|--------|
| SQL Injection | PDO con prepared statements en **todas** las consultas |
| XSS | Twig escapa automáticamente + `htmlspecialchars()` en PHP puro |
| Contraseñas | bcrypt con `password_hash()` / `password_verify()` |
| Fuerza bruta login | Rate limiting con tabla `LoginIntentos` |
| CSRF | Tokens de sesión en formularios sensibles |
| Acceso no autorizado | Verificación de `$_SESSION['role']` en cada endpoint protegido |
| Datos sensibles | `.env` fuera del control de versiones, credenciales nunca en el código |

### Prepared statements — ¿por qué son importantes?

Sin ellos:
```php
// PELIGROSO — SQL injection
$sql = "SELECT * FROM Usuarios WHERE email = '$email'";
// Si $email = "' OR '1'='1", devuelve todos los usuarios
```

Con prepared statements:
```php
// SEGURO
$stmt = $conn->prepare("SELECT * FROM Usuarios WHERE email = ?");
$stmt->execute([$email]);
// El valor de $email nunca se interpreta como SQL
```

---

## 13. TESTS CON PHPUNIT

### ¿Qué testamos?

Los tests están en `tests/prueba_unitaria/`. El archivo principal es `TransactionModelTest.php`.

### ¿Cómo correr los tests?

```bash
vendor/bin/phpunit --testdox
vendor/bin/phpunit tests/prueba_unitaria/TransactionModelTest.php --testdox
```

### Tipos de tests

- **Unitarios**: prueban un método de un modelo de forma aislada
- **Integración**: verifican que el flujo completo (PHP → MySQL) funciona

### Ejemplo de test unitario

```php
public function testTransicionValida(): void
{
    // Arrange
    $transaccion = ['estado' => 'pendiente', 'comprador_id' => 1];
    
    // Act
    $resultado = $this->transaction->puedeTransicionar($transaccion, 'aceptada', 1, false);
    
    // Assert
    $this->assertTrue($resultado);
}
```

---

## 14. API REST — ENDPOINTS

### Productos

| Método | Ruta | Para qué |
|--------|------|---------|
| GET | `/api/getProductsPaginated.php` | Home con scroll infinito |
| GET | `/api/search_products.php` | Búsqueda con todos los filtros |
| GET | `/api/mis_productos_activos.php` | Mis productos (para ofrecer en trueque) |
| GET | `/api/get_filters.php` | Categorías y estados disponibles |

### Chat y transacciones

| Método | Ruta | Para qué |
|--------|------|---------|
| GET | `/api/chat_messages.php` | Polling de mensajes nuevos |
| GET | `/api/chat_unread_count.php` | Badge de no leídos en navbar |
| POST | `/api/chat_mark_all_read.php` | Marcar como leídos |
| POST | `/api/stripe_create_payment.php` | Crear PaymentIntent |
| GET | `/api/mis_transacciones.php` | Historial de transacciones |

### Otros

| Método | Ruta | Para qué |
|--------|------|---------|
| GET/POST | `/api/notificaciones.php` | Leer/marcar notificaciones |
| GET | `/api/get_rating.php` | Media de valoraciones de un usuario |
| GET | `/api/normalize_address.php` | Proxy Nominatim |
| GET | `/api/check_session.php` | Verificar si hay sesión activa |

### Formato de respuesta

Todos los endpoints devuelven JSON:
```json
{
  "success": true,
  "data": [...],
  "total": 42,
  "page": 1
}
```
En caso de error:
```json
{
  "success": false,
  "error": "Descripción del error"
}
```

---

## 15. PREGUNTAS FRECUENTES DEL TRIBUNAL

**¿Por qué PHP y no Node.js o Python?**
> PHP es el lenguaje que hemos estudiado en el módulo, se integra nativamente con Apache/XAMPP y MySQL, y es ampliamente usado en desarrollo web. Para un proyecto de este tamaño es más que suficiente.

**¿Por qué no usáis un framework como Laravel?**
> Decidimos usar PHP nativo para entender mejor los fundamentos: cómo funciona PDO, la gestión de sesiones, el enrutamiento manual. Con Laravel mucho de esto queda oculto.

**¿Cómo garantizáis la seguridad?**
> Tres pilares: prepared statements en todas las consultas SQL (previene inyección), Twig con escape automático en las vistas (previene XSS) y bcrypt para las contraseñas (previene ataques de diccionario).

**¿El chat es en tiempo real de verdad?**
> Usa polling HTTP cada 3 segundos. No es WebSocket puro, pero para el caso de uso (negociación entre dos personas) es suficiente y mucho más simple de implementar.

**¿Stripe funciona con dinero real?**
> En el entorno actual está en modo **test**. Se puede pagar con la tarjeta de prueba `4242 4242 4242 4242`. Para producción solo habría que cambiar las claves del `.env`.

**¿Qué pasa si dos personas intentan comprar el mismo producto a la vez?**
> La primera transacción que se crea bloquea el producto. La segunda persona recibirá un error indicando que el producto ya tiene una transacción activa (RN-06).

**¿Por qué Nominatim y no Google Maps?**
> Nominatim es gratuito, no requiere API key y no tiene límite de facturación. Google Maps tiene coste a partir de cierto volumen de peticiones. Para un marketplace escolar, OpenStreetMap es la opción más adecuada.

**¿Cómo funciona el trueque?**
> El comprador ve un producto de tipo `intercambio` o `mixto`. Al iniciar la transacción, puede seleccionar uno de sus propios productos activos para ofrecerlo a cambio. El ID del producto ofrecido se guarda en `Intercambio_Detalle`. Ambos productos aparecen en el panel de transacción como comparativa visual.

**¿Qué hace exactamente el panel de admin?**
> El admin puede ver y gestionar usuarios (cambiar rol, bloquear), moderar productos y resolver reportes de la comunidad, y exportar datos en CSV para análisis externo.

**¿Cómo está organizado el código? ¿Hay algún estándar?**
> Seguimos convenciones consistentes: prepared statements siempre, `htmlspecialchars()` en salidas PHP directas, `intval()`/`trim()` para sanitizar inputs, los modelos reciben la conexión PDO por constructor y no acceden directamente a `$_SESSION`.
