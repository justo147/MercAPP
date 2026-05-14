# MercApp 🛒

> Marketplace de compra, venta e intercambio desarrollado como proyecto integrador de **2.º DAW**.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.3-7952B3?logo=bootstrap&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-11.x-6C9B3A?logo=php&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-v3-635BFF?logo=stripe&logoColor=white)
![License](https://img.shields.io/badge/licencia-académica-lightgrey)

---

## Índice

1. [Descripción](#descripción)
2. [Funcionalidades](#funcionalidades)
3. [Stack tecnológico](#stack-tecnológico)
4. [Arquitectura](#arquitectura)
5. [Estructura del proyecto](#estructura-del-proyecto)
6. [Base de datos](#base-de-datos)
7. [Máquina de estados — Transacciones](#máquina-de-estados--transacciones)
8. [Pago con tarjeta — Stripe](#pago-con-tarjeta--stripe)
9. [API REST](#api-rest)
10. [Vistas](#vistas)
11. [Mejoras UX](#mejoras-ux)
12. [Instalación local (XAMPP)](#instalación-local-xampp)
13. [Variables de entorno](#variables-de-entorno)
14. [Tests](#tests)
15. [Convenciones de código](#convenciones-de-código)

---

## Descripción

**MercApp** es una aplicación web de marketplace que permite a usuarios registrados publicar artículos para **venta**, **intercambio** o ambos (**mixto**). Los compradores pueden contactar al vendedor mediante un sistema de **chat integrado**, gestionar **transacciones** con seguimiento de estado, dejar **valoraciones** y guardar artículos en **favoritos** o en una **lista de deseos** con alertas automáticas.

Incluye un **panel de administración** completo con gestión de usuarios, productos, reportes y exportación de datos a CSV.

---

## Funcionalidades

### Usuario
- Registro con verificación de email y recuperación de contraseña
- Perfil público con historial de productos y reputación media (⭐)
- Publicación de productos con hasta 6 imágenes, geocodificación automática de ubicación (OpenStreetMap / Nominatim) y coordenadas lat/lon para búsqueda por proximidad
- Búsqueda avanzada: texto libre, categoría, precio, estado, tipo de transacción, ordenación por distancia (radio configurable)
- Sistema de **favoritos** y **lista de deseos** con matching automático y notificación al publicarse un producto coincidente
- **Chat** entre comprador y vendedor con mensajes de sistema y seguimiento de transacción
- **Seguir usuarios**: feed personalizado con novedades de seguidos y sugerencias de usuarios a seguir
- **Valoraciones** tras la entrega (fiabilidad, comunicación, puntualidad)
- Notificaciones in-app con badge y polling cada 30 s (mensajes, coincidencias, valoraciones, moderación)
- Modo oscuro persistente (sesión + localStorage)
- hCaptcha en registro para protección anti-bots

### Transacciones
- Flujo guiado de 6 estados con transiciones por rol (comprador / vendedor)
- Elección de método de pago: efectivo, transferencia, Bizum, PayPal, otro o **tarjeta de crédito/débito vía Stripe**
- Pago con tarjeta integrado mediante **Stripe.js v3** + PaymentIntent (flujo seguro PCI-compliant)
- Dirección de envío con autocomplete Nominatim
- Número de seguimiento de paquete
- Email de confirmación al completar la entrega

### Administración
- Dashboard con estadísticas globales (usuarios, productos, transacciones, valoraciones)
- Gestión de usuarios: cambio de rol, suspensión, eliminación
- Gestión de productos: activar / pausar / eliminar
- Gestión de reportes: revisar, aceptar, rechazar
- Exportación de usuarios y transacciones a **CSV** (compatible con Excel)

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.x + PDO (prepared statements) |
| Base de datos | MySQL 8 — charset `utf8mb4` |
| Frontend | Bootstrap 5.3.3 · Bootstrap Icons 1.11.1 · SASS/SCSS |
| Email | PHPMailer 6.x — SMTP Gmail |
| Tests | PHPUnit 11.x |
| Configuración | `vlucas/phpdotenv` 5.x |
| Geocodificación | Nominatim (OpenStreetMap) — sin API key |
| Pagos | Stripe.js v3 + PaymentIntent API |
| Documentación | PHPDocumentor 3.9 |

---

## Arquitectura

El proyecto sigue una **arquitectura MVC ligera** sin framework, ejecutada sobre XAMPP.

```
Petición HTTP
     │
     ▼
public/views/*.php          ← Vistas (HTML + PHP mínimo)
     │
     ├─► controllers/        ← Controladores y handlers de formulario
     │         │
     │         ▼
     │       models/         ← Clases de acceso a datos (PDO)
     │         │
     │         ▼
     │       config/db.php   ← Conexión PDO (Database)
     │
     └─► api/*.php           ← Endpoints JSON para llamadas AJAX
```

**Reglas clave:**
- Prepared statements PDO en **toda** consulta SQL.
- `htmlspecialchars()` en todas las salidas HTML.
- `intval()` / `trim()` al recibir cualquier input.
- Los modelos reciben `PDO $conn` por constructor (inyección de dependencias manual).
- `$BASE` (ruta base `/MercApp`) disponible en cualquier archivo tras `require_once config/bootstrap.php`.
- `const BASE` declarado **una sola vez** en `navbar.php` para uso en JavaScript.

---

## Estructura del proyecto

```
MercApp/
│
├── api/                        # 25 endpoints JSON
├── config/
│   ├── bootstrap.php           # Carga .env, define $BASE
│   ├── db.php                  # Clase Database → PDO
│   ├── flash.php               # Helpers setFlash() / hasFlash() para mensajes de sesión
│   └── mail_config.php         # PHPMailer SMTP
│
├── controllers/
│   ├── AuthController.php      # Login, registro, verificación, recuperación
│   ├── handlers/               # 14 procesadores de formularios (POST)
│   ├── chat_start.php
│   ├── chat_start_transaction.php
│   ├── chat_update_transaction.php
│   ├── follow.php / unfollow.php
│   └── logout.php
│
├── models/
│   ├── User.php
│   ├── Product.php
│   ├── Transaction.php
│   ├── Chat.php
│   ├── Message.php
│   ├── Notification.php
│   ├── Rating.php
│   ├── Report.php
│   └── RateLimiter.php
│
├── public/
│   ├── views/                  # 21 plantillas PHP
│   ├── js/                     # 15 scripts JavaScript (incluye ux.js)
│   ├── css/                    # CSS compilado
│   ├── scss/                   # Fuentes SASS
│   └── img/ · ico/ · fonts/    # Recursos estáticos
│
├── uploads/products/           # Imágenes subidas por usuarios (WebP)
├── migrations/                 # Scripts SQL de migración incremental
├── tests/                      # PHPUnit — 8 suites de pruebas
├── docs/                       # PHPDoc generado automáticamente
│
├── bd.sql                      # Schema completo ← fuente de verdad
├── ejemplo-pruebas.sql         # Datos de prueba
├── composer.json
├── index.php                   # Entry point → redirecciona a login
└── .env                        # Variables de entorno (no committear)
```

---

## Base de datos

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `Usuario` | Usuarios con rol (`registrado` / `admin`) y estado (`activo` / `suspendido` / `eliminado`) |
| `Productos` | Artículos con ubicación, lat/lon, tipo de transacción y estado de publicación |
| `Imagenes_prod` | Imágenes por producto (múltiples, ordenadas, almacenadas como WebP) |
| `Categorias` | Categorías fijas del marketplace |
| `EstadoProducto` | Estado del artículo: nuevo, como nuevo, bueno, regular |
| `EstadoPublicacion` | Estado de publicación: activo, pausado, vendido |
| `Transacciones` | Máquina de 6 estados con método de pago, dirección de envío y nº seguimiento |
| `Intercambio_Detalle` | Detalle de intercambios multi-producto |
| `Valoraciones` | Puntuaciones 1–5 por fiabilidad, comunicación y puntualidad |
| `vw_usuario_reputacion` | Vista calculada de reputación media por usuario |
| `Favoritos` | Productos guardados como favoritos |
| `Deseos` | Wishlist con etiquetas, categoría y estado deseado |
| `Chat` | Conversaciones producto-comprador-vendedor |
| `Mensajes` | Mensajes de chat (`usuario_id = NULL` → mensaje de sistema) |
| `Notificaciones` | Tipos: `mensaje`, `coincidencia`, `valoracion`, `moderacion` |
| `Reportes` | Reportes de contenido con estado (pendiente / revisado / rechazado) |
| `Seguidores` | Relación many-to-many de seguimiento entre usuarios |
| `LoginIntentos` | Rate limiting: bloqueo por IP tras 5 intentos fallidos en 15 min |

### Levantar el schema

```sql
-- En phpMyAdmin o consola MySQL:
CREATE DATABASE mercapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mercapp;
SOURCE bd.sql;
SOURCE ejemplo-pruebas.sql;   -- opcional, carga datos de prueba
```

---

## Máquina de estados — Transacciones

```
pendiente
   │  Comprador acepta + elige método de pago + dirección de envío
   ▼
aceptada
   │  Comprador informa que ha pagado
   ▼
pago_pendiente
   │  Vendedor confirma recepción del pago + añade nº seguimiento
   ▼
enviado
   │  Comprador confirma recepción del paquete
   ▼
entregado  ✅  (estado final positivo)

Cualquier estado  →  cancelada  ❌  (estado final negativo)
```

| Transición | Actor |
|-----------|-------|
| `pendiente → aceptada` | Comprador |
| `aceptada → pago_pendiente` | Comprador |
| `pago_pendiente → enviado` | Vendedor |
| `enviado → entregado` | Comprador |
| `* → cancelada` | Cualquiera |

---

## Pago con tarjeta — Stripe

El flujo de pago con tarjeta sigue el patrón **PaymentIntent** de Stripe para mayor seguridad (nunca se envían datos de tarjeta al servidor propio):

```
1. Comprador selecciona "tarjeta" como método de pago
2. El frontend carga Stripe.js y monta el Card Element
3. Al confirmar, se llama a POST /api/stripe_create_payment.php
   → El servidor crea un PaymentIntent con el importe y devuelve client_secret
4. El frontend llama a stripe.confirmCardPayment(client_secret, { payment_method: { card } })
5. Si el pago es exitoso (status = "succeeded"):
   → Se avanza el estado de la transacción a pago_pendiente automáticamente
```

| Variable `.env` | Descripción |
|----------------|-------------|
| `STRIPE_SECRET_KEY` | Clave secreta de Stripe (empieza por `sk_`) |
| `STRIPE_PUBLISHABLE_KEY` | Clave pública de Stripe (empieza por `pk_`) |

> Para desarrollo usa las claves de **test** del dashboard de Stripe. Las tarjetas de prueba (`4242 4242 4242 4242`, CVV cualquiera, fecha futura) no realizan cobros reales.

---

## API REST

Todos los endpoints devuelven `Content-Type: application/json` y requieren sesión activa salvo indicación.

### Productos

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `api/search_products.php` | GET | Búsqueda avanzada (texto, categoría, precio, estado, tipo, distancia Haversine) |
| `api/getProductsPaginated.php` | GET | Listado paginado para el home |
| `api/get_filters.php` | GET | Opciones de filtros con conteo de productos por categoría |
| `api/productos_usuario.php` | GET | Productos activos de un usuario |
| `api/mis_productos_activos.php` | GET | Productos activos del usuario en sesión |
| `api/products_following.php` | GET | Productos de usuarios seguidos (paginado) |

### Deseos

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `api/deseos.php` | GET | Lista deseos con conteo de coincidencias en catálogo |
| `api/deseos.php?accion=matches&id=X` | GET | Productos que encajan con un deseo concreto |
| `api/deseos.php` | POST `accion=add` | Añade un deseo y devuelve coincidencias inmediatas |
| `api/deseos.php` | POST `accion=delete` | Elimina un deseo propio |

### Transacciones y chat

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `api/mis_transacciones.php` | GET | Historial completo de transacciones |
| `api/chat_unread_count.php` | GET | Número de mensajes no leídos |
| `api/chat_mark_all_read.php` | POST | Marca todos los mensajes no leídos como leídos |
| `api/stripe_create_payment.php` | POST | Crea un PaymentIntent de Stripe y devuelve el `client_secret` |

### Notificaciones

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `api/notificaciones.php` | GET | Notificaciones no leídas (máx. 15) |
| `api/notificaciones.php` | POST `accion=mark_all` | Marca todas como leídas |

### Usuario y preferencias

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `api/stats.php` | GET | Estadísticas públicas de un usuario |
| `api/set_theme.php` | POST | Guarda preferencia de tema (claro/oscuro) en sesión |
| `api/normalize_address.php` | GET `?q=texto` | Proxy Nominatim — autocomplete de direcciones españolas |

### Administración *(requiere rol `admin`)*

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `api/admin_stats.php` | GET | Estadísticas globales del marketplace |
| `api/admin_get_users.php` | GET | Lista paginada de usuarios con búsqueda |
| `api/admin_get_products.php` | GET | Lista paginada de productos con búsqueda |
| `api/admin_get_reports.php` | GET | Lista paginada de reportes |
| `api/admin_change_role.php` | POST | Cambiar rol de usuario |
| `api/admin_update_status.php` | POST | Actualizar estado de usuario |
| `api/admin_update_product.php` | POST | Cambiar estado de publicación de producto |
| `api/admin_update_report.php` | POST | Actualizar estado de reporte |
| `api/admin_export_usuarios.php` | GET | Exportar usuarios a CSV (BOM UTF-8) |
| `api/admin_export_transacciones.php` | GET | Exportar transacciones a CSV (BOM UTF-8) |

---

## Vistas

| Vista | Descripción |
|-------|-------------|
| `landing_page.php` | Página de inicio pública |
| `auth/login.php` | Inicio de sesión |
| `auth/register.php` | Registro con hCaptcha |
| `verify_email.php` | Verificación de email por token |
| `forgot_pass.php` / `reset_password.php` | Recuperación de contraseña |
| `home.php` | Feed principal con búsqueda, filtros y búsqueda por proximidad |
| `detail_product.php` | Detalle de producto con galería, carrusel de sugeridos y valoraciones |
| `upload_product.php` | Publicar nuevo producto con autocomplete de ubicación |
| `mod_product.php` | Editar producto propio |
| `profile.php` | Perfil público de usuario con reputación y productos |
| `detail_account.php` | Ajustes de cuenta (email, contraseña, foto de perfil) |
| `my_transactions.php` | Historial de transacciones (compras y ventas) |
| `my_favorites.php` | Productos guardados como favoritos |
| `my_wishlist.php` | Lista de deseos con matching de productos en catálogo |
| `chat_list.php` | Lista de chats con filtros (abiertos, cerrados, con transacción…) |
| `chat.php` | Conversación individual y gestión del flujo de transacción |
| `followers_products.php` | Feed de seguidos + lista de siguiendo + sugerencias |
| `admin_dashboard.php` | Panel de administración completo |
| `docs.php` | Documentación técnica (PHPDoc, JSDoc, Tests, API) |
| `help.php` | Preguntas frecuentes y ayuda |

---

## Mejoras UX

Conjunto de mejoras de experiencia de usuario implementadas sobre el diseño base:

### Sistema de feedback unificado (`public/js/ux.js` + `public/js/navbar.js`)
- **Toast notifications**: `mostrarToast(mensaje, tipo)` disponible globalmente — soporta `success`, `error`, `warning`, `info`. Reemplaza todos los `alert()` y `confirm()` nativos del navegador.
- **Mensajes flash de sesión**: `setFlash(tipo, mensaje)` en PHP (`config/flash.php`) — los mensajes persisten a través de redirects y se emiten como toasts al cargar la siguiente página.
- **Confirmación de acciones destructivas**: modales Bootstrap en lugar de `confirm()` nativo en chat, wishlist y panel de administración.

### Estados de carga
- **Botón de carga**: `data-loading-text="..."` en cualquier botón de submit activa automáticamente un spinner y deshabilita el botón durante el envío del formulario, evitando dobles envíos.
- **Skeleton loaders**: tarjetas placeholder (`placeholder-glow`) en el home, perfil de usuario y panel de administración mientras se cargan los datos vía AJAX.
- **Spinner en botones de seguir**: los botones "Seguir/Dejar de seguir" muestran un spinner giratorio hasta recibir respuesta del servidor.

### Protección de datos
- **Aviso de cambios no guardados**: `data-unsaved-warning` en formularios activa un aviso `beforeunload` si el usuario intenta salir con cambios sin guardar.
- **Autoguardado de borrador**: los formularios de publicar y editar producto guardan automáticamente los campos cada 5 s en `localStorage` y los restauran al volver a la página.
- **Contadores de caracteres**: campos `titulo` (máx. 100) y `descripcion` (máx. 2000) muestran contador en tiempo real con aviso en rojo al acercarse al límite.

### Búsqueda y filtros (home)
- **Chips de filtros activos**: cada filtro aplicado genera una píldora con botón ✕ para quitarlo individualmente o limpiar todos a la vez.
- **Contador de resultados**: texto dinámico "N productos encontrados" con `aria-live="polite"` para lectores de pantalla.
- **Empty state diferenciado**: mensaje y CTA distintos según haya filtros aplicados o no.
- **Spinner de scroll infinito**: indicador visible mientras se cargan más productos.
- **Reintentar en error**: botón "Reintentar" cuando falla la carga de productos.

### Detección offline
- Banner rojo fijo en la parte superior cuando el navegador pierde la conexión a Internet; desaparece automáticamente al recuperarla.

### Registro
- **Toggle de contraseña**: botón ojo en los campos de contraseña para mostrar/ocultar el texto.
- **Indicador de fortaleza**: barra de progreso de 5 niveles (débil → muy fuerte) en tiempo real.
- **Validación de confirmación**: feedback inmediato con ✓/✗ mientras el usuario escribe en "Confirmar contraseña".

### Chat
- **Marcar todo como leído**: botón en la lista de chats que marca todos los mensajes no leídos de una sola acción, con actualización inmediata del badge de la navbar.

---

## Instalación local (XAMPP)

### Requisitos

- XAMPP con **PHP 8.x** y **MySQL 8.x**
- **Composer**
- **Git**

### Pasos

```bash
# 1. Clonar el repositorio dentro de htdocs
cd C:/xampp/htdocs
git clone https://github.com/justo147/MercAPP.git MercApp

# 2. Instalar dependencias PHP
cd MercApp
composer install

# 3. Crear la base de datos
#    Abrir phpMyAdmin → crear BD 'mercapp' → importar bd.sql
#    (Opcional) importar ejemplo-pruebas.sql para datos de prueba

# 4. Configurar variables de entorno
copy .env.example .env
# Editar .env con tus valores

# 5. Crear directorio de uploads
mkdir uploads\products

# 6. Iniciar Apache y MySQL desde el panel de XAMPP
#    Acceder a: http://localhost/MercApp
```

---

## Variables de entorno

Crea un archivo `.env` en la raíz del proyecto:

```ini
# Ruta base (debe coincidir con el nombre de carpeta en htdocs)
BASE_PATH=/MercApp

# Base de datos
DB_HOST=localhost
DB_NAME=mercapp
DB_USERNAME=root
DB_PASS=

# Email — Gmail App Password
# Genera una en: Cuenta Google → Seguridad → Contraseñas de aplicación
EMAIL_API_KEY=xxxx xxxx xxxx xxxx

# hCaptcha
# Claves de prueba para desarrollo (funcionan sin registro):
#   Sitekey: 10000000-ffff-ffff-ffff-000000000001
#   Secret:  0x0000000000000000000000000000000000000000
HCAPTCHA_SECRET=0x0000000000000000000000000000000000000000

# Stripe — pagos con tarjeta
# Obtén tus claves en: https://dashboard.stripe.com/apikeys
# Usa claves "test" (sk_test_... / pk_test_...) para desarrollo
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

> ⚠️ **Nunca subas el `.env` con credenciales reales.** Ya está incluido en `.gitignore`.

---

## Tests

El proyecto incluye **8 suites PHPUnit** que cubren los modelos principales.

```bash
# Ejecutar todos los tests
vendor/bin/phpunit --testdox

# Ejecutar una suite concreta
vendor/bin/phpunit tests/prueba_unitaria/TransactionModelTest.php --testdox
```

| Suite | Cobertura |
|-------|-----------|
| `TransactionModelTest` | Máquina de estados, creación, validaciones de transición |
| `UserModelTest` | Autenticación, verificación de email, recuperación de contraseña |
| `ProductModelTest` | CRUD, búsqueda con filtros, paginación, gestión de imágenes |
| `ChatModelTest` | Creación, obtención y cierre de chats |
| `MessageModelTest` | Mensajes de usuario y mensajes de sistema |
| `NotificationModelTest` | Creación, lectura y marcado de notificaciones |
| `RatingModelTest` | Valoraciones y cálculo de reputación media |
| `ReportModelTest` | Creación de reportes y cambio de estado |

---

## Convenciones de código

- **SQL:** Prepared statements PDO siempre — nunca concatenar variables en queries.
- **Output HTML:** `htmlspecialchars()` en todas las salidas dinámicas.
- **Input:** `intval()` y `trim()` al recibir datos del usuario.
- **Modelos:** Reciben `PDO $conn` por constructor.
- **JavaScript:** `const BASE` declarado una sola vez en `navbar.php`. Nunca redeclarar en otras vistas.
- **Imágenes subidas:** Convertidas a WebP (calidad 80, máx. 900 px de ancho) antes de guardar.
- **Rate limiting:** 5 intentos fallidos de login en 15 minutos → bloqueo por IP.

---

## Autores

**Justo** — Proyecto de fin de ciclo · 2.º DAW  
**Sergio** — Proyecto de fin de ciclo · 2.º DAW  
**Noelia** — Proyecto de fin de ciclo · 2.º DAW  
**Juan Jesus** — Proyecto de fin de ciclo · 2.º DAW  
🔗 [github.com/justo147/MercAPP](https://github.com/justo147/MercAPP)
