# MercApp — Guía para Claude

## Qué es este proyecto
Marketplace escolar en **PHP puro + MySQL** con arquitectura MVC ligera. Sin frameworks. Se ejecuta en XAMPP local.

## Stack
| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.x + PDO |
| Base de datos | MySQL (`mercapp`, user `root`, pass vacío) |
| Frontend | Bootstrap 5.3.3 + Bootstrap Icons 1.11.1 |
| Email | PHPMailer via SMTP Gmail |
| Tests | PHPUnit 11.x |
| Config | `vlucas/phpdotenv` + `.env` |

## Estructura de carpetas
```
/
├── api/                    # Endpoints JSON (admin, búsqueda, stats, normalize_address)
├── config/
│   ├── bootstrap.php       # Carga .env, define $BASE (/MercApp)
│   ├── db.php              # Clase Database → PDO
│   └── mail_config.php     # PHPMailer SMTP
├── controllers/
│   ├── handlers/           # Procesadores de formularios
│   ├── chat_start_transaction.php
│   └── chat_update_transaction.php
├── models/
│   ├── Transaction.php     # ⭐ Transacciones (ver estado máquina abajo)
│   ├── Product.php
│   ├── User.php
│   ├── Chat.php
│   ├── Message.php
│   ├── Rating.php
│   └── Report.php
├── public/
│   ├── views/              # Plantillas PHP (Bootstrap)
│   ├── js/
│   │   └── address_autocomplete.js  # ⭐ Autocomplete con Nominatim
│   └── css/
├── tests/prueba_unitaria/  # PHPUnit
├── bd.sql                  # Schema completo (fuente de verdad)
├── ejemplo-pruebas.sql     # Datos de prueba
└── .env                    # No commitear
```

## Variables de sesión
```php
$_SESSION['user_id']       // int
$_SESSION['email']
$_SESSION['name']
$_SESSION['profile_photo']
$_SESSION['role']          // 'registrado' | 'admin'
```

## Estado máquina de transacciones (actualizado)
```
pendiente
  ↓ comprador acepta + elige método de pago + dirección de envío
aceptada
  ↓ comprador informa que ha pagado
pago_pendiente
  ↓ vendedor confirma que recibió el pago
enviado  (vendedor añade número de seguimiento)
  ↓ comprador confirma recepción
entregado  ✓ final positivo
    
cualquier estado → cancelada  ✗ final negativo
```

### Quién puede hacer cada transición
| Transición | Actor |
|-----------|-------|
| pendiente → aceptada | Comprador (aporta método de pago + dirección) |
| aceptada → pago_pendiente | Comprador (indica que ha pagado) |
| pago_pendiente → enviado | Vendedor (añade nº seguimiento) |
| enviado → entregado | Comprador |
| * → cancelada | Cualquiera |

## Normalización de direcciones — Nominatim (OpenStreetMap)
- **Proxy PHP:** `api/normalize_address.php` — recibe `?q=texto` y devuelve JSON
- **JS:** `public/js/address_autocomplete.js` — autocomplete reutilizable
- **API externa:** `https://nominatim.openstreetmap.org/search?q=...&format=json&countrycodes=es&limit=5`
- Sin API key. Respetar User-Agent y límite de 1 req/seg.
- Se usa en: campo `ubicacion` de productos + campo `direccion_envio` de transacciones

## Nuevos campos en tabla `Transacciones`
```sql
metodo_pago          ENUM('efectivo','transferencia','bizum','paypal','otro') NULL
direccion_envio      VARCHAR(300) NULL
numero_seguimiento   VARCHAR(100) NULL
fecha_aceptacion     DATETIME NULL
fecha_pago_confirmado DATETIME NULL
fecha_envio          DATETIME NULL
fecha_entrega        DATETIME NULL
notas_comprador      TEXT NULL
```

## Convenciones de código
- Prepared statements PDO siempre (nunca concatenar SQL)
- `htmlspecialchars()` en todas las salidas HTML
- `intval()` / `trim()` al recibir input
- Clases modelo reciben `PDO $conn` por constructor
- Vistas incluyen navbar con `include("navbar.php")`
- `$BASE` disponible tras `require_once config/bootstrap.php`

## Ejecutar tests
```bash
vendor/bin/phpunit --testdox
vendor/bin/phpunit tests/prueba_unitaria/TransactionModelTest.php --testdox
```
