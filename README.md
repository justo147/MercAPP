# MercApp 🛒

> Marketplace de compra, venta e intercambio desarrollado como proyecto integrador de **2.º DAW**.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.3-7952B3?logo=bootstrap&logoColor=white)
![Twig](https://img.shields.io/badge/Twig-3.x-bacf29?logo=symfony&logoColor=white)
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
7. [Migraciones](#migraciones)
8. [Máquina de estados — Transacciones](#máquina-de-estados--transacciones)
9. [Pago con tarjeta — Stripe](#pago-con-tarjeta--stripe)
10. [Chat y panel de transacción](#chat-y-panel-de-transacción)
11. [API REST](#api-rest)
12. [Vistas](#vistas)
13. [Diseño y UX](#diseño-y-ux)
14. [Instalación local (XAMPP)](#instalación-local-xampp)
15. [Variables de entorno](#variables-de-entorno)
16. [Tests](#tests)
17. [Convenciones de código](#convenciones-de-código)

---

## Descripción

**MercApp** es una aplicación web de marketplace que permite a usuarios registrados publicar artículos para **venta**, **intercambio** o ambos (**mixto**). Los compradores pueden contactar al vendedor mediante un sistema de **chat integrado**, gestionar **transacciones** con seguimiento de estado, dejar **valoraciones** y guardar artículos en **favoritos** o en una **lista de deseos** con alertas automáticas.

Incluye un **panel de administración** completo con gestión de usuarios, productos, reportes y exportación de datos a CSV.

El frontend ha sido rediseñado completamente usando **Twig 3** como motor de plantillas y un sistema de diseño propio inspirado en plataformas como Wallapop o Vinted, con soporte nativo para **modo oscuro** sin parpadeo.

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
- Modo oscuro persistente con antiparpadeo — sin flash al cargar la página
- hCaptcha en registro para protección anti-bots

### Transacciones
- Flujo guiado de 6 estados con transiciones por rol (comprador / vendedor)
- Tipos de transacción: **venta**, **intercambio** (trueque de productos) y **mixto** (producto + dinero)
- Elección de método de pago: efectivo, transferencia, Bizum, PayPal, otro o **tarjeta de crédito/débito vía Stripe**
- Pago con tarjeta integrado mediante **Stripe.js v3** + PaymentIntent (flujo seguro PCI-compliant)
- Intercambio de productos: el comprador propone su producto desde sus activos; ambas partes ven los artículos cruzados
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
| Motor de plantillas | **Twig 3.x** — todas las vistas son templates `.html.twig` |
| Frontend | Bootstrap 5.3.3 · Bootstrap Icons 1.11.1 · Inter (Google Fonts) |
| Sistema de diseño | CSS custom properties — tema claro/oscuro vía `data-theme` en `<html>` |
| Email | PHPMailer 6.x — SMTP Gmail |
| Tests | PHPUnit 11.x |
| Configuración | `vlucas/phpdotenv` 5.x |
| Geocodificación | Nominatim (OpenStreetMap) — sin API key |
| Pagos | Stripe.js v3 + PaymentIntent API |
| Documentación | PHPDocumentor 3.9 |

---

## Arquitectura

El proyecto sigue una **arquitectura MVC ligera** sin framework, ejecutada sobre XAMPP. Las vistas PHP delegan todo el HTML a **Twig**, actuando únicamente como controladores finos.

```
Petición HTTP
     │
     ▼
public/views/*.php          ← Controladores finos: lógica + $twig->render()
     │
     ├─► controllers/        ← Handlers de formularios y lógica de negocio
     │         │
     │         ▼
     │       models/         ← Clases de acceso a datos (PDO)
     │         │
     │         ▼
     │       config/db.php   ← Conexión PDO (Database)
     │
     ├─► templates/          ← Plantillas Twig (.html.twig)
     │         ├── base.html.twig          (layout principal)
     │         ├── base_auth.html.twig     (layout páginas de auth)
     │         ├── components/             (navbar, footer)
     │         ├── auth/                   (login, registro…)
     │         └── *.html.twig             (resto de vistas)
     │
     └─► api/*.php           ← Endpoints JSON para llamadas AJAX
```

**Reglas clave:**
- Prepared statements PDO en **toda** consulta SQL.
- Twig escapa automáticamente el output HTML (`|e`). Nunca usar `|raw` con datos de usuario.
- `intval()` / `trim()` al recibir cualquier input en PHP.
- Los modelos reciben `PDO $conn` por constructor (inyección de dependencias manual).
- `$BASE` (ruta base `/MercApp`) disponible en cualquier archivo tras `require_once config/bootstrap.php`. También expuesto como global Twig y como `const BASE` en JS desde `base.html.twig`.

---

## Estructura del proyecto

```
MercApp/
│
├── api/                        # 25 endpoints JSON
├── config/
│   ├── bootstrap.php           # Carga .env, define $BASE
│   ├── db.php                  # Clase Database → PDO
│   ├── twig.php                # ⭐ Entorno Twig (globals, filtros, funciones)
│   ├── flash.php               # Helpers setFlash() / hasFlash()
│   └── mail_config.php         # PHPMailer SMTP
│
├── controllers/
│   ├── handlers/               # 14 procesadores de formularios (POST)
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
├── templates/                  # ⭐ Plantillas Twig
│   ├── base.html.twig          # Layout principal (navbar + footer + dark mode)
│   ├── base_auth.html.twig     # Layout auth (sin navbar)
│   ├── components/
│   │   ├── navbar.html.twig
│   │   └── footer.html.twig
│   ├── auth/
│   │   ├── login.html.twig
│   │   ├── register.html.twig
│   │   └── pending_verification.html.twig
│   ├── home.html.twig
│   ├── profile.html.twig
│   ├── detail_product.html.twig
│   ├── upload_product.html.twig
│   ├── mod_product.html.twig
│   ├── chat.html.twig
│   ├── chat_list.html.twig
│   ├── my_transactions.html.twig
│   ├── my_favorites.html.twig
│   ├── my_wishlist.html.twig
│   ├── followers_products.html.twig
│   ├── detail_account.html.twig
│   ├── admin_dashboard.html.twig
│   ├── help.html.twig
│   ├── docs.html.twig
│   ├── forgot_pass.html.twig
│   ├── reset_password.html.twig
│   └── verify_email.html.twig
│
├── public/
│   ├── views/                  # Controladores finos PHP (delegan HTML a Twig)
│   ├── js/                     # Scripts JavaScript
│   │   ├── theme.js            # Toggle data-theme en <html>, sin parpadeo
│   │   ├── ux.js               # Toasts, spinners, offline banner…
│   │   ├── navbar.js           # Notificaciones, badge de mensajes
│   │   └── address_autocomplete.js  # Autocomplete Nominatim
│   ├── css/
│   │   └── app.css             # ⭐ Sistema de diseño completo (CSS custom props)
│   └── img/ · ico/ · fonts/    # Recursos estáticos
│
├── uploads/products/           # Imágenes subidas por usuarios (WebP)
├── tests/                      # PHPUnit — 8 suites de pruebas
├── migrations/                 # Migraciones SQL incrementales
│   ├── 001_transacciones_realistas.sql
│   ├── 002_rate_limiting_intercambio.sql
│   ├── 003_productos_coordenadas.sql
│   └── 004_stripe_payment_intent.sql
│
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

## Migraciones

Las migraciones son archivos SQL incrementales para actualizar una base de datos existente sin recrearla. Se encuentran en `migrations/` y deben aplicarse en orden.

| Archivo | Descripción |
|---------|-------------|
| `001_transacciones_realistas.sql` | Amplía el ENUM de `estado` con `pago_pendiente`, añade `metodo_pago`, `direccion_envio`, `notas_comprador` y timestamps de cada paso |
| `002_rate_limiting_intercambio.sql` | Crea la tabla `LoginIntentos` para rate limiting y añade la tabla `Intercambio_Detalle` |
| `003_productos_coordenadas.sql` | Añade columnas `lat` y `lon` a `Productos` para búsqueda por proximidad |
| `004_stripe_payment_intent.sql` | Añade `stripe_payment_intent_id`, `numero_seguimiento`, `fecha_aceptacion`, `fecha_pago_confirmado`, `fecha_envio` y `fecha_entrega` a `Transacciones` |

### Cómo aplicar una migración

```sql
-- En phpMyAdmin: selecciona la BD mercapp → pestaña SQL → pega el contenido del archivo
-- Todas usan IF NOT EXISTS / IF EXISTS para ser idempotentes
```

> ⚠️ Los archivos `001` y `002` contienen `USE mercapp;` — si los ejecutas desde phpMyAdmin con la BD ya seleccionada, elimina esa línea o ignora el aviso.

---

## Máquina de estados — Transacciones

```
pendiente
   │  Comprador acepta + elige método de pago + dirección de envío
   │  (en intercambios: propone también su producto a cambio)
   ▼
aceptada
   │  Comprador informa que ha pagado
   │  (con Stripe: salta directamente a pago_pendiente)
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

| Transición | Actor | Datos extra |
|-----------|-------|-------------|
| `pendiente → aceptada` | Comprador | `metodo_pago`, `direccion_envio`, `notas_comprador`, `producto_ofrecido_id` (intercambio) |
| `aceptada → pago_pendiente` | Comprador | — |
| `pago_pendiente → enviado` | Vendedor | `numero_seguimiento` (opcional) |
| `enviado → entregado` | Comprador | — (envía email de confirmación a ambas partes) |
| `* → cancelada` | Cualquiera | El producto vuelve a estado `activo` |

### Tipos de transacción

| Tipo | Descripción | Método de pago requerido |
|------|-------------|--------------------------|
| `venta` | Compra/venta estándar | Sí |
| `intercambio` | Trueque puro de productos | No — el comprador propone su producto |
| `mixto` | Producto + compensación económica | Sí + producto a cambio |

---

## Chat y panel de transacción

La vista de chat (`chat.php`) usa un **layout de dos columnas** en escritorio:

```
┌─────────────────────────────────────────────────────┐
│  [Imagen + Título del producto]      [Reportar]     │
├────────────────────────┬────────────────────────────┤
│                        │  Panel de transacción       │
│   Mensajes del chat    │  ┌─ Timeline vertical ──┐  │
│   (scroll interno)     │  │  ● Pendiente          │  │
│                        │  │  │ Aceptada           │  │
│   [burbuja saliente]   │  │  │ Pago               │  │
│   [burbuja entrante]   │  │  │ Enviado            │  │
│   [mensaje sistema]    │  │  │ Entregado          │  │
│                        │  └───────────────────────┘  │
│                        │  [Solo la acción actual]    │
│                        │  [Cancelar transacción]     │
├────────────────────────┴────────────────────────────┤
│  [Escribe un mensaje…]                      [Enviar] │
└─────────────────────────────────────────────────────┘
```

**Panel de transacción — comportamiento por estado y rol:**

| Estado | Comprador ve | Vendedor ve |
|--------|-------------|-------------|
| `pendiente` | Formulario de aceptación (pago + dirección + producto si es intercambio) | "Esperando al comprador" |
| `aceptada` | Botón "Ya he pagado" | "Esperando confirmación de pago" |
| `pago_pendiente` | "Pago notificado" / confirmación Stripe | Formulario de nº seguimiento + "Confirmar envío" |
| `enviado` | Botón "He recibido el producto" | "Esperando confirmación" |
| `entregado` | Tarjeta de éxito + modal de valoración | Tarjeta de éxito |
| `cancelada` | Tarjeta de cancelación | Tarjeta de cancelación |

En móvil las columnas se apilan (chat arriba, panel abajo) y el panel de transacción no es sticky.

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
| `STRIPE_KEY` | Clave pública de Stripe (empieza por `pk_`) |
| `STRIPE_WEBHOOK_SECRET` | Secret del webhook de Stripe (empieza por `whsec_`) |

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

Todas las vistas PHP actúan como controladores finos: ejecutan la lógica, consultan los modelos y llaman a `$twig->render('template.html.twig', $datos)`. El HTML reside íntegramente en `templates/`.

| Vista (PHP) | Template Twig | Descripción |
|-------------|---------------|-------------|
| `landing_page.php` | — | Página de inicio pública |
| `auth/login.php` | `auth/login.html.twig` | Inicio de sesión |
| `auth/register.php` | `auth/register.html.twig` | Registro con hCaptcha |
| `verify_email.php` | `verify_email.html.twig` | Verificación de email por token |
| `forgot_pass.php` | `forgot_pass.html.twig` | Solicitar recuperación de contraseña |
| `reset_password.php` | `reset_password.html.twig` | Restablecer contraseña con token |
| `home.php` | `home.html.twig` | Feed principal con búsqueda, filtros y scroll infinito |
| `detail_product.php` | `detail_product.html.twig` | Detalle de producto con galería, sugeridos y valoraciones |
| `upload_product.php` | `upload_product.html.twig` | Publicar nuevo producto con autocomplete de ubicación |
| `mod_product.php` | `mod_product.html.twig` | Editar producto propio |
| `profile.php` | `profile.html.twig` | Perfil público con reputación, seguidores y productos |
| `detail_account.php` | `detail_account.html.twig` | Ajustes de cuenta (datos personales y foto) |
| `my_transactions.php` | `my_transactions.html.twig` | Historial de transacciones (compras y ventas) |
| `my_favorites.php` | `my_favorites.html.twig` | Productos guardados como favoritos |
| `my_wishlist.php` | `my_wishlist.html.twig` | Lista de deseos con matching en catálogo |
| `chat_list.php` | `chat_list.html.twig` | Lista de chats con filtros |
| `chat.php` | `chat.html.twig` | Chat individual — layout dos columnas: mensajes izquierda, panel de transacción con timeline derecha |
| `followers_products.php` | `followers_products.html.twig` | Feed de seguidos + sugerencias |
| `admin_dashboard.php` | `admin_dashboard.html.twig` | Panel de administración completo |
| `docs.php` | `docs.html.twig` | Documentación técnica (PHPDoc, JSDoc, Tests, API) |
| `help.php` | `help.html.twig` | Preguntas frecuentes y ayuda |

---

## Diseño y UX

### Sistema de diseño (`public/css/app.css`)

Toda la interfaz se basa en **CSS custom properties** declaradas en `:root`, lo que permite cambiar el tema completo con un solo atributo en el `<html>`:

```css
/* Tema claro (por defecto) */
:root {
  --c-primary: #038065;   /* verde MercApp */
  --c-bg:      #f7f8fa;
  --c-surface: #ffffff;
  --c-text:    #111827;
  /* … */
}

/* Tema oscuro */
[data-theme="dark"] {
  --c-bg:      #0f1117;
  --c-surface: #1a1d27;
  --c-text:    #f1f5f9;
  /* … */
}
```

### Modo oscuro sin parpadeo

El script de antiparpadeo se ejecuta **inline en `<head>`** antes de pintar la página, leyendo `localStorage` y aplicando `data-theme` sobre `<html>` de inmediato. El botón de toggle actualiza el atributo y persiste la preferencia tanto en `localStorage` como en la sesión del servidor (`api/set_theme.php`).

### Componentes Twig

- **`base.html.twig`** — layout principal con navbar, footer, toast container y scripts core
- **`base_auth.html.twig`** — layout minimalista para páginas de autenticación (sin navbar)
- **`components/navbar.html.twig`** — navbar sticky: logo izquierda, buscador centro (desktop), iconos derecha
- **`components/footer.html.twig`** — footer de cuatro columnas

### Feedback y UX (`public/js/ux.js`)

- **`mostrarToast(msg, tipo)`** — toasts Bootstrap accesibles (`success`, `error`, `warning`, `info`)
- **`data-loading-text="…"`** en botones de submit → spinner automático + deshabilita el botón
- **`data-unsaved-warning`** en formularios → aviso `beforeunload` si hay cambios sin guardar
- **Autoguardado de borrador** en upload/edición de producto (localStorage, cada 5 s)
- **Contadores de caracteres** en campos con `maxlength`
- **Banner offline** — detecta `navigator.onLine` y muestra aviso rojo automáticamente
- **Skeleton loaders** en home, perfil y panel de administración

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

# 2. Instalar dependencias PHP (incluye Twig y phpdotenv)
cd MercApp
composer install

# 3. Crear la base de datos
#    Abrir phpMyAdmin → crear BD 'mercapp' → importar bd.sql
#    (Opcional) importar ejemplo-pruebas.sql para datos de prueba
#    Si ya tenías la BD creada, aplica las migraciones de migrations/ en orden

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
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
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
- **Output HTML:** Twig escapa automáticamente. En PHP puro, usar `htmlspecialchars()`. Nunca `|raw` con datos de usuario.
- **Input:** `intval()` y `trim()` al recibir datos del usuario.
- **Modelos:** Reciben `PDO $conn` por constructor.
- **Twig globals:** `BASE`, `session`, `year` disponibles en todas las plantillas. Función `asset('ruta')` para URLs de `public/`.
- **JavaScript:** `const BASE` declarado una sola vez en `base.html.twig`. Nunca redeclarar en otras vistas.
- **Imágenes subidas:** Convertidas a WebP (calidad 80, máx. 900 px de ancho) antes de guardar.
- **Rate limiting:** 5 intentos fallidos de login en 15 minutos → bloqueo por IP.

---

## Autores

**Justo** — Proyecto de fin de ciclo · 2.º DAW  
**Sergio** — Proyecto de fin de ciclo · 2.º DAW  
**Noelia** — Proyecto de fin de ciclo · 2.º DAW  
**Juan Jesus** — Proyecto de fin de ciclo · 2.º DAW  
🔗 [github.com/justo147/MercAPP](https://github.com/justo147/MercAPP)
