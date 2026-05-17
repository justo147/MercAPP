# MercApp — Guía técnica completa para la exposición
### Escrita para que cualquier miembro del equipo pueda explicar cómo funciona la aplicación

> **Cómo usar esta guía:** Lee cada sección despacio. Cada concepto tiene una explicación en lenguaje normal antes del código técnico. Si no entiendes el código, al menos entiende la explicación en palabras. Con eso es suficiente para la exposición.

---

# ÍNDICE

1. [¿Qué es MercApp y cómo está organizado?](#1-qué-es-mercapp-y-cómo-está-organizado)
2. [El servidor — cómo funciona XAMPP y Apache](#2-el-servidor--cómo-funciona-xampp-y-apache)
3. [La arquitectura MVC — cómo está dividido el código](#3-la-arquitectura-mvc--cómo-está-dividido-el-código)
4. [La base de datos — MySQL y PDO](#4-la-base-de-datos--mysql-y-pdo)
5. [Las tablas de la base de datos — explicadas una a una](#5-las-tablas-de-la-base-de-datos--explicadas-una-a-una)
6. [El motor de plantillas Twig — cómo se generan las páginas](#6-el-motor-de-plantillas-twig--cómo-se-generan-las-páginas)
7. [El sistema de autenticación — registro, login y sesiones](#7-el-sistema-de-autenticación--registro-login-y-sesiones)
8. [Los productos — publicar, editar, buscar](#8-los-productos--publicar-editar-buscar)
9. [La búsqueda geográfica — Nominatim y Haversine](#9-la-búsqueda-geográfica--nominatim-y-haversine)
10. [El sistema de chat — cómo funciona el polling](#10-el-sistema-de-chat--cómo-funciona-el-polling)
11. [Las transacciones — la máquina de estados](#11-las-transacciones--la-máquina-de-estados)
12. [El trueque — cómo funciona el intercambio](#12-el-trueque--cómo-funciona-el-intercambio)
13. [Stripe — pagos con tarjeta](#13-stripe--pagos-con-tarjeta)
14. [El sistema de valoraciones](#14-el-sistema-de-valoraciones)
15. [El sistema de notificaciones](#15-el-sistema-de-notificaciones)
16. [El panel de administración](#16-el-panel-de-administración)
17. [La seguridad — cómo protegemos la aplicación](#17-la-seguridad--cómo-protegemos-la-aplicación)
18. [La API REST — los endpoints JSON](#18-la-api-rest--los-endpoints-json)
19. [Los tests con PHPUnit](#19-los-tests-con-phpunit)
20. [El modo oscuro](#20-el-modo-oscuro)
21. [Variables de entorno — el archivo .env](#21-variables-de-entorno--el-archivo-env)
22. [Composer — el gestor de dependencias](#22-composer--el-gestor-de-dependencias)
23. [Preguntas que puede hacer el tribunal — con respuestas completas](#23-preguntas-que-puede-hacer-el-tribunal--con-respuestas-completas)

---

# 1. ¿QUÉ ES MERCAPP Y CÓMO ESTÁ ORGANIZADO?

## ¿Qué hace MercApp?

MercApp es una web de segunda mano. Permite a los usuarios:
- Publicar productos que quieren vender o intercambiar
- Buscar productos de otros usuarios filtrando por categoría, precio, distancia, etc.
- Hablar con el vendedor mediante un chat
- Realizar transacciones de tres tipos: **venta** (dinero), **intercambio** (trueque) o **mixto** (producto + dinero)
- Pagar con tarjeta de crédito a través de Stripe
- Valorar al otro usuario al terminar la transacción
- Reportar productos inapropiados

## ¿Qué lo diferencia de Wallapop o Vinted?

La gran diferencia es el **trueque nativo**. En Wallapop puedes decir en la descripción "acepto cambios" pero no hay ningún sistema que gestione eso. En MercApp existe una funcionalidad completa donde el comprador puede seleccionar uno de sus propios productos y ofrecerlo a cambio, y todo queda registrado en la base de datos.

## ¿Cómo está organizado el código?

Piensa en el proyecto como si fuera una empresa con departamentos:

```
MercApp/
│
├── api/                  → El departamento de información
│                           Contesta preguntas rápidas en formato JSON
│                           (¿hay mensajes nuevos?, ¿qué productos hay?)
│
├── config/               → La sala de control
│                           Aquí está la configuración de todo:
│                           conexión a BD, configuración de email, etc.
│
├── controllers/          → El departamento de operaciones
│                           Procesa los formularios (registro, login,
│                           publicar producto, enviar mensaje...)
│
├── models/               → El departamento de datos
│                           Clases PHP que saben cómo leer y escribir
│                           en la base de datos
│
├── templates/            → El departamento de diseño
│                           Archivos HTML con el aspecto visual
│                           de cada página
│
├── public/               → Lo que ve el usuario directamente
│   ├── js/               → JavaScript del navegador
│   └── css/              → Estilos visuales
│
├── migrations/           → Historial de cambios en la BD
│
├── tests/                → Control de calidad
│
├── bd.sql                → El plano completo de la base de datos
└── .env                  → Contraseñas y configuración secreta
```

---

# 2. EL SERVIDOR — CÓMO FUNCIONA XAMPP Y APACHE

## ¿Qué es XAMPP?

XAMPP es un paquete que instala en tu ordenador todo lo necesario para tener un servidor web funcionando localmente. Incluye:
- **Apache**: el servidor web (como si fuera un camarero que recibe peticiones)
- **MySQL**: la base de datos (como si fuera el almacén donde se guarda todo)
- **PHP**: el lenguaje de programación (como si fueran las instrucciones que sigue el camarero)

## ¿Qué pasa cuando alguien visita una página de MercApp?

Vamos a seguir el recorrido de una petición paso a paso:

```
1. El usuario escribe en el navegador:
   http://localhost/MercApp/

2. El navegador envía una petición a Apache
   (como enviar un pedido en un restaurante)

3. Apache recibe la petición y busca qué archivo PHP ejecutar

4. PHP ejecuta el archivo, que puede:
   - Leer datos de MySQL (consultar la carta del restaurante)
   - Procesar información
   - Generar HTML para devolver al navegador

5. El navegador recibe el HTML y lo muestra al usuario
```

## ¿Por qué se llama "local"?

Porque el servidor está en nuestro propio ordenador. La dirección `localhost` significa "este mismo ordenador". Para que otros usuarios puedan acceder, necesitaríamos un servidor en internet (hosting).

---

# 3. LA ARQUITECTURA MVC — CÓMO ESTÁ DIVIDIDO EL CÓDIGO

## ¿Qué significa MVC?

MVC son las siglas de **Modelo - Vista - Controlador**. Es una forma de organizar el código separándolo en tres partes con responsabilidades distintas.

**Analogía con un restaurante:**
- **Modelo** = La cocina. Prepara los datos, accede a los ingredientes (base de datos)
- **Vista** = El plato que llega a la mesa. Lo que ve el cliente (HTML)
- **Controlador** = El camarero. Recibe el pedido, se lo pasa a la cocina y lleva el plato

## El Modelo — `models/`

Los modelos son clases PHP. Cada clase corresponde a una entidad del sistema:

- `Product.php` → todo lo relacionado con productos
- `User.php` → todo lo relacionado con usuarios
- `Transaction.php` → todo lo relacionado con transacciones
- `Chat.php` → todo lo relacionado con chats
- `Message.php` → todo lo relacionado con mensajes
- `Rating.php` → todo lo relacionado con valoraciones
- `Report.php` → todo lo relacionado con reportes

¿Qué hacen estos archivos? Contienen funciones (métodos) que hablan con la base de datos. Por ejemplo, en `Product.php`:

```php
class Product {
    private $conn;  // la conexión a la base de datos

    // Constructor: recibe la conexión cuando se crea el objeto
    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // Método para obtener todos los productos activos
    public function getActiveProducts($limit, $offset) {
        $sql = "SELECT p.*, u.nombre AS vendedor_nombre
                FROM Productos p
                JOIN Usuarios u ON p.usuario_id = u.id
                WHERE p.estado = 'activo'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

**Lo importante:** El modelo NO sabe nada de cómo se va a mostrar la información. Solo sabe cómo obtenerla y guardarla.

## La Vista — `templates/`

Las vistas son archivos `.html.twig`. Contienen el HTML de cada página. No tienen lógica de negocio, solo muestran los datos que les pasa el controlador.

Ejemplo simplificado de `home.html.twig`:
```html
{% extends 'base.html.twig' %}

{% block content %}
  <h1>Productos disponibles</h1>

  {% for producto in productos %}
    <div class="card">
      <img src="{{ producto.imagen }}">
      <h2>{{ producto.titulo }}</h2>
      <p>{{ producto.precio }}€</p>
    </div>
  {% endfor %}
{% endblock %}
```

**Lo importante:** La vista NO accede a la base de datos. Solo muestra lo que le dan.

## El Controlador — `controllers/`

Los controladores son scripts PHP que:
1. Reciben los datos del formulario o la URL
2. Validan esos datos
3. Llaman al modelo correspondiente
4. Redirigen o muestran la vista con los resultados

Ejemplo simplificado de cómo funciona la página de inicio:

```php
// index.php (simplificado)
require_once 'config/bootstrap.php';
$conn = Database::getConnection();

$productModel = new Product($conn);
$productos = $productModel->getActiveProducts(12, 0);

// Renderiza la vista pasándole los productos
echo $twig->render('home.html.twig', [
    'productos' => $productos,
    'usuario'   => $_SESSION ?? null
]);
```

## El flujo completo con un ejemplo real

Cuando un usuario publica un producto:

```
1. Usuario rellena el formulario en templates/upload_product.html.twig
   (Vista)

2. Hace clic en "Publicar" → el navegador envía los datos a:
   controllers/handlers/upload_product_handler.php
   (Controlador)

3. El controlador:
   - Comprueba que el usuario está logueado
   - Valida que los campos no estén vacíos
   - Procesa las imágenes (las convierte a WebP)
   - Llama al modelo: $product->create($datos)
   (Controlador llama al Modelo)

4. El modelo inserta el producto en MySQL
   (Modelo accede a la BD)

5. El controlador redirige a la página del producto recién creado
```

---

# 4. LA BASE DE DATOS — MYSQL Y PDO

## ¿Qué es MySQL?

MySQL es un sistema de base de datos. Piensa en él como una hoja de cálculo Excel gigante, pero mucho más potente. En vez de hojas, tiene **tablas**. En vez de filas de Excel, tiene **registros**. En vez de columnas de Excel, tiene **campos**.

La base de datos de MercApp se llama `mercapp` y la gestiona XAMPP en nuestro ordenador.

## ¿Qué es PDO?

PDO significa **PHP Data Objects**. Es la forma que tiene PHP de hablar con MySQL. Sin PDO, PHP no sabría cómo enviar preguntas a MySQL ni recibir respuestas.

Para conectarse a la base de datos, usamos la clase `Database` que está en `config/db.php`:

```php
class Database {
    private static $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Datos de conexión (vienen del archivo .env)
            $host = $_ENV['DB_HOST'];     // 'localhost'
            $name = $_ENV['DB_NAME'];     // 'mercapp'
            $user = $_ENV['DB_USER'];     // 'root'
            $pass = $_ENV['DB_PASS'];     // '' (vacío en XAMPP)

            self::$instance = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass
            );

            // Si hay un error SQL, lanza una excepción en vez de ignorarlo
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }
}
```

**¿Por qué hay un `self::$instance`?** Para no crear una nueva conexión cada vez. La primera vez que se llama a `getConnection()`, crea la conexión. Las siguientes veces, devuelve la misma. Esto se llama patrón **Singleton**.

## ¿Qué es una consulta SQL?

SQL es el lenguaje que se usa para hacer preguntas y dar órdenes a la base de datos. Ejemplos:

```sql
-- Obtener todos los productos activos
SELECT * FROM Productos WHERE estado = 'activo';

-- Insertar un nuevo usuario
INSERT INTO Usuarios (nombre, email, password_hash)
VALUES ('Justo', 'justo@email.com', '$2y$10$...');

-- Actualizar el estado de una transacción
UPDATE Transacciones SET estado = 'enviado' WHERE id = 42;

-- Borrar un producto
DELETE FROM Productos WHERE id = 7;
```

## ¿Por qué usamos "prepared statements"?

Un prepared statement es una forma segura de hacer consultas SQL. En vez de poner los datos directamente en la consulta (peligroso), usamos `?` como marcadores:

```php
// PELIGROSO - NO hacemos esto:
$sql = "SELECT * FROM Usuarios WHERE email = '$email'";
// Si alguien escribe: email = "' OR '1'='1"
// La consulta se convierte en: WHERE email = '' OR '1'='1'
// ¡Y devuelve TODOS los usuarios!

// SEGURO - Así sí lo hacemos:
$stmt = $conn->prepare("SELECT * FROM Usuarios WHERE email = ?");
$stmt->execute([$email]);
// PDO se encarga de que $email nunca se interprete como SQL
```

Esto se llama **prevenir SQL Injection** y es una de las medidas de seguridad más importantes.

---

# 5. LAS TABLAS DE LA BASE DE DATOS — EXPLICADAS UNA A UNA

## Tabla: Usuarios

Guarda toda la información de cada persona registrada.

| Campo | Tipo | Para qué sirve |
|-------|------|----------------|
| id | INT (auto) | Identificador único de cada usuario |
| nombre | VARCHAR(100) | Nombre completo |
| email | VARCHAR(150) | Email (único, se usa para login) |
| password_hash | VARCHAR(255) | Contraseña encriptada con bcrypt |
| rol | ENUM | 'registrado' o 'admin' |
| foto_perfil | VARCHAR(300) | Ruta a la imagen de perfil |
| telefono | VARCHAR(20) | Teléfono (opcional) |
| ubicacion | VARCHAR(200) | Texto de ubicación |
| lat / lon | DECIMAL | Coordenadas GPS de su ubicación |
| email_verificado | TINYINT(1) | 0 = pendiente, 1 = verificado |
| activo | TINYINT(1) | 0 = bloqueado por admin, 1 = activo |
| token_verificacion | VARCHAR(64) | Token para verificar el email |
| token_reset | VARCHAR(64) | Token para recuperar contraseña |
| created_at | DATETIME | Cuándo se registró |

## Tabla: Productos

Guarda los anuncios publicados.

| Campo | Tipo | Para qué sirve |
|-------|------|----------------|
| id | INT (auto) | Identificador único del producto |
| usuario_id | INT (FK) | Quién publicó el producto |
| titulo | VARCHAR(150) | Título del anuncio |
| descripcion | TEXT | Descripción detallada |
| precio | DECIMAL(10,2) | Precio en euros |
| tipo_transaccion | ENUM | 'venta', 'intercambio' o 'mixto' |
| categoria_id | INT (FK) | A qué categoría pertenece |
| estado_producto_id | INT (FK) | Nuevo / Como nuevo / Bueno / Regular |
| estado | ENUM | 'activo', 'pausado' o 'vendido' |
| ubicacion | VARCHAR(200) | Texto de la dirección |
| lat / lon | DECIMAL | Coordenadas GPS del producto |
| created_at | DATETIME | Cuándo se publicó |

**¿Qué es FK?** FK significa "Foreign Key" (clave foránea). Es un campo que apunta al `id` de otra tabla. Por ejemplo, `usuario_id` en Productos apunta al `id` de Usuarios. Así sabemos quién publicó cada producto.

## Tabla: Imagenes_Producto

Un producto puede tener varias fotos. Esta tabla las almacena.

| Campo | Para qué |
|-------|---------|
| id | Identificador de la imagen |
| producto_id | A qué producto pertenece (FK) |
| url | Ruta al archivo de imagen en el servidor |
| orden | 1 = portada, 2, 3... para el resto |

**¿Por qué una tabla aparte?** Porque si pusiéramos las imágenes directamente en Productos, solo podríamos tener una imagen por producto (o tendríamos que crear columnas imagen1, imagen2... que es muy feo). Con una tabla separada, podemos tener tantas imágenes como queramos.

## Tabla: Categorias

Lista de categorías de productos. Ej: Electrónica, Ropa, Libros...

| Campo | Para qué |
|-------|---------|
| id | Identificador |
| nombre | Nombre de la categoría |

## Tabla: EstadosProducto

Lista de posibles estados físicos de un producto. Ej: Nuevo, Como nuevo, Bueno, Regular.

## Tabla: Chats

Cada chat conecta a un comprador con un vendedor sobre un producto concreto.

| Campo | Para qué |
|-------|---------|
| id | Identificador del chat |
| producto_id | Sobre qué producto se habla (FK) |
| comprador_id | Quién quiere comprar (FK → Usuarios) |
| vendedor_id | Quién vende (FK → Usuarios) |
| created_at | Cuándo se creó |

**Punto importante:** El chat está vinculado al **producto**, no a la transacción. Si la transacción se cancela y se inicia una nueva, se reutiliza el mismo chat.

## Tabla: Mensajes

Cada mensaje individual dentro de un chat.

| Campo | Para qué |
|-------|---------|
| id | Identificador del mensaje |
| chat_id | En qué chat está este mensaje (FK) |
| usuario_id | Quién lo envió (FK → Usuarios, puede ser NULL) |
| contenido | El texto del mensaje |
| leido | 0 = no leído, 1 = leído |
| created_at | Cuándo se envió |

**¿Por qué usuario_id puede ser NULL?** Porque los mensajes automáticos del sistema (como "La transacción ha sido aceptada") no los envía ningún usuario real. Al ser NULL, el sistema los muestra de forma diferente (centrados, con estilo gris).

## Tabla: Transacciones

Es la tabla más importante y compleja. Registra el acuerdo entre comprador y vendedor.

| Campo | Para qué |
|-------|---------|
| id | Identificador de la transacción |
| chat_id | Chat asociado (FK) |
| producto_id | Producto en cuestión (FK) |
| comprador_id | Quien compra (FK → Usuarios) |
| vendedor_id | Quien vende (FK → Usuarios) |
| estado | pendiente / aceptada / pago_pendiente / enviado / entregado / cancelada |
| tipo | venta / intercambio / mixto |
| precio_final | Precio en el momento de crear la transacción |
| dinero_extra | En transacciones mixtas, cuánto dinero extra se paga |
| metodo_pago | efectivo / transferencia / bizum / paypal / stripe / otro |
| direccion_envio | Dirección donde se envía el producto |
| numero_seguimiento | Número de seguimiento del envío |
| notas_comprador | Instrucciones especiales del comprador |
| stripe_payment_intent_id | ID del pago en Stripe (para verificación) |
| fecha_aceptacion | Cuándo se aceptó |
| fecha_pago_confirmado | Cuándo se confirmó el pago |
| fecha_envio | Cuándo se marcó como enviado |
| fecha_entrega | Cuándo se confirmó la entrega |

## Tabla: Intercambio_Detalle

Cuando el tipo de transacción es 'intercambio' o 'mixto', el comprador ofrece uno de sus propios productos.

| Campo | Para qué |
|-------|---------|
| id | Identificador |
| transaccion_id | A qué transacción pertenece (FK) |
| producto_ofrecido_id | Qué producto ofrece el comprador (FK → Productos) |

## Tabla: Valoraciones

Las puntuaciones que se dan los usuarios mutuamente al terminar una transacción.

| Campo | Para qué |
|-------|---------|
| id | Identificador |
| transaccion_id | De qué transacción viene (FK) |
| evaluador_id | Quién pone la valoración (FK → Usuarios) |
| evaluado_id | Quién recibe la valoración (FK → Usuarios) |
| fiabilidad | Puntuación 1-5 |
| comunicacion | Puntuación 1-5 |
| puntualidad | Puntuación 1-5 |
| comentario | Texto opcional |
| created_at | Cuándo se valoró |

## Tabla: Notificaciones

Las alertas que recibe cada usuario (nuevo mensaje, transacción aceptada, etc.)

| Campo | Para qué |
|-------|---------|
| id | Identificador |
| usuario_id | A quién va dirigida (FK) |
| tipo | Tipo de notificación (string) |
| mensaje | Texto de la notificación |
| url | Enlace para ir al contexto de la notificación |
| leida | 0 = no leída, 1 = leída |
| created_at | Cuándo se creó |

## Tabla: Reportes

Denuncias de productos que hacen los usuarios.

| Campo | Para qué |
|-------|---------|
| id | Identificador |
| reportador_id | Quién denuncia (FK → Usuarios) |
| producto_id | Qué producto se denuncia (FK → Productos) |
| motivo | Texto explicando el motivo |
| estado | pendiente / revisado / descartado |
| created_at | Cuándo se reportó |

## Tabla: LoginIntentos

Registra los intentos de login fallidos para evitar ataques de fuerza bruta.

| Campo | Para qué |
|-------|---------|
| id | Identificador |
| email | Email con el que se intentó entrar |
| ip | Dirección IP del ordenador que intentó entrar |
| created_at | Cuándo fue el intento |

---

# 6. EL MOTOR DE PLANTILLAS TWIG — CÓMO SE GENERAN LAS PÁGINAS

## ¿Qué es Twig y para qué sirve?

Twig es un motor de plantillas. En PHP tradicional, para mostrar variables en HTML mezclas PHP y HTML así:

```php
<!-- PHP tradicional — difícil de leer -->
<h1>Hola <?php echo htmlspecialchars($usuario['nombre']); ?></h1>
<?php if ($usuario['rol'] === 'admin'): ?>
  <a href="admin.php">Panel Admin</a>
<?php endif; ?>
```

Con Twig es mucho más limpio:

```twig
<!-- Con Twig — mucho más legible -->
<h1>Hola {{ usuario.nombre }}</h1>
{% if usuario.rol == 'admin' %}
  <a href="admin.php">Panel Admin</a>
{% endif %}
```

Además, Twig añade **escape automático**: si una variable contiene código HTML malicioso, lo neutraliza automáticamente. Esto previene ataques XSS (Cross-Site Scripting).

## Sintaxis básica de Twig

```twig
{{ variable }}                    → Imprime el valor de una variable
{{ objeto.propiedad }}            → Accede a una propiedad de un objeto/array
{{ variable|upper }}              → Aplica un filtro (pone en mayúsculas)
{{ precio|number_format(2,',','.') }} → Formatea un número

{% if condicion %}                → Condición
  ...
{% elseif otra_condicion %}
  ...
{% else %}
  ...
{% endif %}

{% for item in lista %}           → Bucle
  {{ loop.index }} - {{ item.nombre }}
{% endfor %}

{% set variable = valor %}        → Declarar una variable

{# Esto es un comentario #}       → Comentarios (no aparecen en el HTML)
```

## La herencia de plantillas — la clave de Twig

La funcionalidad más importante de Twig es la **herencia**. Funciona como una plantilla de Word: defines la estructura base una sola vez y cada página solo rellena las partes que cambian.

### base.html.twig (estructura común a todas las páginas)

```twig
<!DOCTYPE html>
<html lang="es" data-theme="{{ tema ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}MercApp{% endblock %}</title>
    <link rel="stylesheet" href="bootstrap.css">
    {% block head_extra %}{% endblock %}
</head>
<body>
    {% include 'navbar.html.twig' %}

    <main>
        {% block content %}
        {# Aquí va el contenido específico de cada página #}
        {% endblock %}
    </main>

    {% include 'footer.html.twig' %}

    <script src="bootstrap.js"></script>
    {% block scripts %}{% endblock %}
</body>
</html>
```

### home.html.twig (hereda de base)

```twig
{% extends 'base.html.twig' %}

{% block title %}Inicio — MercApp{% endblock %}

{% block content %}
  <div class="container">
    <h1>Productos disponibles</h1>
    {% for producto in productos %}
      <!-- tarjeta de producto -->
    {% endfor %}
  </div>
{% endblock %}
```

**¿Qué pasa cuando se carga home.html.twig?**
- Twig coge base.html.twig como esqueleto
- Sustituye `{% block title %}` con "Inicio — MercApp"
- Sustituye `{% block content %}` con la lista de productos
- El navbar y el footer aparecen automáticamente en todas las páginas

## Cómo se configura Twig — `config/twig.php`

```php
// Le decimos a Twig dónde están las plantillas
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');

// Creamos el entorno de Twig
$twig = new \Twig\Environment($loader, [
    'cache'       => __DIR__ . '/../cache',  // Guarda versiones compiladas
    'auto_reload' => true,                   // Recompila si cambia la plantilla
]);
```

La caché de Twig (carpeta `cache/`) guarda versiones PHP compiladas de las plantillas para que sean más rápidas. Por eso no se sube a Git (está en .gitignore).

---

# 7. EL SISTEMA DE AUTENTICACIÓN — REGISTRO, LOGIN Y SESIONES

## ¿Qué es una sesión?

Cuando entras en una web, el servidor necesita recordar quién eres mientras navegas por las páginas. HTTP (el protocolo de internet) es "sin estado" — cada petición es independiente y el servidor no recuerda nada de la anterior.

Las **sesiones** resuelven esto: el servidor guarda información sobre ti en memoria y te da un identificador único (guardado en una cookie). Cada vez que haces una petición, envías ese identificador y el servidor sabe quién eres.

En PHP, la sesión se maneja con `$_SESSION`:

```php
// Al hacer login, guardamos los datos del usuario en sesión
$_SESSION['user_id']       = 42;
$_SESSION['email']         = 'justo@email.com';
$_SESSION['name']          = 'Justo';
$_SESSION['profile_photo'] = 'uploads/users/foto.webp';
$_SESSION['role']          = 'registrado'; // o 'admin'

// En cualquier otra página, podemos leer estos datos
if (!isset($_SESSION['user_id'])) {
    // No hay sesión → redirigir al login
    header('Location: /login');
    exit;
}
```

## El proceso de registro

**Paso 1:** El usuario rellena el formulario en `templates/register.html.twig`
- Nombre, email, contraseña, confirmar contraseña

**Paso 2:** El formulario envía los datos a `controllers/handlers/register_handler.php`

**Paso 3:** El controlador valida:
- ¿El email ya existe en la BD? → Error
- ¿La contraseña tiene al menos 8 caracteres? → Error si no
- ¿Las contraseñas coinciden? → Error si no

**Paso 4:** Encripta la contraseña:
```php
// Nunca guardamos la contraseña en texto plano
// bcrypt genera un hash diferente cada vez (incluye "sal" aleatoria)
$hash = password_hash($password, PASSWORD_DEFAULT);
// Resultado: algo como "$2y$10$abc123def456..."
```

**Paso 5:** Genera un token de verificación:
```php
// bin2hex convierte bytes aleatorios en texto hexadecimal
$token = bin2hex(random_bytes(32));
// Resultado: una cadena de 64 caracteres aleatorios
```

**Paso 6:** Inserta el usuario en la BD con `email_verificado = 0`

**Paso 7:** Envía un email con el enlace de verificación usando PHPMailer:
```php
$mail = new PHPMailer(true);
$mail->setFrom('mercapp@gmail.com', 'MercApp');
$mail->addAddress($email);
$mail->Subject = 'Verifica tu cuenta en MercApp';
$mail->Body = "Haz clic aquí para verificar: http://localhost/MercApp/verify?token=$token";
$mail->send();
```

## La verificación del email

Cuando el usuario hace clic en el enlace del email:
1. El enlace lleva a `controllers/handlers/verify_handler.php?token=XXXXXXXX`
2. El controlador busca el token en la BD
3. Si existe y no ha expirado → actualiza `email_verificado = 1`
4. Si no existe o expiró → muestra error

Hasta verificar, si el usuario intenta acceder a páginas protegidas, es redirigido a `templates/pending.html.twig` que le indica que verifique su email.

## El proceso de login

**Paso 1:** Usuario introduce email y contraseña en `templates/login.html.twig`

**Paso 2:** El controlador `controllers/handlers/login_handler.php`:

**Paso 2a — Rate limiting:** Comprueba cuántos intentos fallidos ha habido:
```php
// ¿Ha habido más de 5 intentos en los últimos 15 minutos desde esta IP?
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM LoginIntentos
     WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
);
$stmt->execute([$_SERVER['REMOTE_ADDR']]);
if ($stmt->fetchColumn() >= 5) {
    die('Demasiados intentos. Espera 15 minutos.');
}
```

**Paso 2b — Verificar credenciales:**
```php
// Busca el usuario por email
$stmt = $conn->prepare("SELECT * FROM Usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario) {
    // Email no existe → registrar intento fallido
    guardarIntentoFallido($ip, $email);
    die('Credenciales incorrectas');
}

// Comprueba la contraseña contra el hash guardado
if (!password_verify($password, $usuario['password_hash'])) {
    guardarIntentoFallido($ip, $email);
    die('Credenciales incorrectas');
}

// Todo correcto → crear sesión
$_SESSION['user_id'] = $usuario['id'];
// etc.
```

**Nota de seguridad:** Siempre decimos "credenciales incorrectas" sin especificar si es el email o la contraseña. Si dijéramos "ese email no existe", estaríamos revelando qué emails están registrados.

## Recuperación de contraseña

1. Usuario introduce su email en `templates/forgot.html.twig`
2. Se genera un token con caducidad de 1 hora
3. Se guarda en la BD: `token_reset` y `token_reset_expira`
4. Se envía email con enlace
5. Usuario hace clic → `templates/reset.html.twig`
6. Introduce nueva contraseña → se hashea y se guarda
7. El token se invalida (se pone a NULL en la BD)

---

# 8. LOS PRODUCTOS — PUBLICAR, EDITAR, BUSCAR

## Publicar un producto

El formulario en `templates/upload_product.html.twig` tiene:
- Título, descripción, precio
- Tipo de transacción (venta/intercambio/mixto)
- Categoría y estado físico del producto
- Campo de ubicación con autocomplete
- Subida de hasta 5 imágenes

### Procesamiento de imágenes

Cuando el usuario sube fotos, el servidor las convierte a formato **WebP**:

```php
// El formato WebP ocupa menos espacio que JPG o PNG
// manteniendo la misma calidad visual

// Detectamos el tipo de imagen original
$tipo = mime_content_type($archivoTemporal);

// Creamos la imagen según su tipo
if ($tipo === 'image/jpeg') {
    $imagen = imagecreatefromjpeg($archivoTemporal);
} elseif ($tipo === 'image/png') {
    $imagen = imagecreatefrompng($archivoTemporal);
}

// Redimensionamos si es muy grande (max 1200px de ancho)
if (imagesx($imagen) > 1200) {
    // calcular nueva altura proporcional y redimensionar
}

// Guardamos como WebP con calidad 85%
$nombreFinal = 'uploads/products/Prod_' . $productoId . '_' . $orden . '.webp';
imagewebp($imagen, $nombreFinal, 85);
```

**¿Por qué WebP?** Porque las imágenes WebP son un 25-30% más pequeñas que las JPG, lo que hace que la web cargue más rápido.

### La primera imagen es la portada

Cuando hay varias imágenes, la que tiene `orden = 1` en la tabla `Imagenes_Producto` es la que aparece como portada en las tarjetas de producto.

## Estados de un producto

- **activo**: visible en el marketplace, cualquiera puede verlo
- **pausado**: el dueño lo ocultó temporalmente (ej: está de vacaciones)
- **vendido**: la transacción se completó, ya no disponible

## Editar y eliminar

- Solo el propietario puede editar o eliminar su producto
- **No se puede editar ni eliminar** si el producto tiene una transacción activa (en estado pendiente, aceptada o enviado)

---

# 9. LA BÚSQUEDA GEOGRÁFICA — NOMINATIM Y HAVERSINE

## El problema que resuelve

Cuando buscas en MercApp, puedes filtrar por proximidad: "solo muéstrame productos a menos de 10 km de mí". Para que esto funcione necesitamos:
1. Saber las coordenadas (latitud y longitud) de cada producto
2. Calcular la distancia entre esas coordenadas y las del usuario
3. Filtrar los que están dentro del radio indicado

## Nominatim — Convertir direcciones en coordenadas

Nominatim es un servicio de OpenStreetMap que convierte texto de dirección en coordenadas GPS. Por ejemplo:
- Entrada: "Calle Mayor 5, Sevilla"
- Salida: `{ lat: 37.3828, lon: -5.9731 }`

Es **completamente gratuito** y no necesita API key (a diferencia de Google Maps que cobra).

### El proxy PHP

Por política de uso de Nominatim, las peticiones deben venir de un servidor, no directamente del navegador. Por eso creamos un proxy:

```
Navegador → api/normalize_address.php → nominatim.openstreetmap.org
```

```php
// api/normalize_address.php
$q = urlencode($_GET['q']);
$url = "https://nominatim.openstreetmap.org/search"
     . "?q={$q}&format=json&countrycodes=es&limit=5";

// El User-Agent es obligatorio por las normas de Nominatim
$opciones = stream_context_create([
    'http' => ['header' => 'User-Agent: MercApp/1.0 (proyecto@ies.es)']
]);

$resultado = file_get_contents($url, false, $opciones);

header('Content-Type: application/json');
echo $resultado;
```

### El autocomplete en el formulario

El archivo `public/js/address_autocomplete.js` es el que hace que aparezcan sugerencias mientras escribes la dirección:

```javascript
// Escucha cuando el usuario escribe en el campo de dirección
campoInput.addEventListener('input', function() {
    const texto = this.value;

    // Solo busca si hay al menos 3 caracteres
    if (texto.length < 3) return;

    // Hace la petición al proxy PHP
    fetch(`/api/normalize_address.php?q=${encodeURIComponent(texto)}`)
        .then(r => r.json())
        .then(resultados => {
            // Muestra las sugerencias en un dropdown
            mostrarSugerencias(resultados);
        });
});

// Cuando el usuario selecciona una sugerencia
function seleccionarSugerencia(resultado) {
    campoInput.value = resultado.display_name;
    campoLat.value = resultado.lat;   // rellena campo oculto
    campoLon.value = resultado.lon;   // rellena campo oculto
    ocultarDropdown();
}
```

Los campos `lat` y `lon` son inputs ocultos en el formulario que se envían junto con la dirección.

## Haversine — Calcular distancias entre coordenadas

La fórmula de Haversine calcula la distancia en kilómetros entre dos puntos del globo terrestre dados en coordenadas. La usamos directamente en SQL:

```sql
SELECT
    p.*,
    (6371 * acos(
        cos(radians(:latUsuario)) * cos(radians(p.lat)) *
        cos(radians(p.lon) - radians(:lonUsuario)) +
        sin(radians(:latUsuario)) * sin(radians(p.lat))
    )) AS distancia_km
FROM Productos p
WHERE p.estado = 'activo'
HAVING distancia_km < :radioKm
ORDER BY distancia_km ASC
```

El número **6371** es el radio de la Tierra en kilómetros. La fórmula devuelve la distancia en línea recta entre dos puntos.

---

# 10. EL SISTEMA DE CHAT — CÓMO FUNCIONA EL POLLING

## ¿Qué es el polling?

El **polling** es una técnica donde el navegador pregunta al servidor periódicamente: "¿Hay algo nuevo?". En nuestro caso, cada 3 segundos.

Alternativa más moderna serían los **WebSockets**, que mantienen una conexión permanente (como una llamada de teléfono). El polling es como enviar mensajes de texto: preguntas, recibes respuesta, preguntas de nuevo.

Para el caso de uso de MercApp (dos personas negociando, no un chat masivo), el polling cada 3 segundos es suficiente y mucho más simple de implementar.

## Cómo funciona el polling en MercApp

### En el frontend (JavaScript)

```javascript
let ultimoIdMensaje = 0; // ID del último mensaje que tenemos

// Función que se ejecuta cada 3 segundos
function pedirMensajosNuevos() {
    fetch(`/api/chat_messages.php?chat_id=${chatId}&after_id=${ultimoIdMensaje}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.messages.length > 0) {
                // Hay mensajes nuevos → mostrarlos
                datos.messages.forEach(msg => mostrarMensaje(msg));

                // Actualizar el ID del último mensaje
                ultimoIdMensaje = datos.messages[datos.messages.length - 1].id;

                // Hacer scroll hasta el final
                hacerScrollAlFinal();
            }
        });
}

// Ejecutar cada 3 segundos
setInterval(pedirMensajosNuevos, 3000);
pedirMensajosNuevos(); // Llamada inmediata al cargar
```

### En el backend (PHP)

```php
// api/chat_messages.php
$chatId  = intval($_GET['chat_id']);
$afterId = intval($_GET['after_id']); // Solo mensajes más nuevos que este ID

$stmt = $conn->prepare(
    "SELECT m.*, u.nombre, u.foto_perfil
     FROM Mensajes m
     LEFT JOIN Usuarios u ON m.usuario_id = u.id
     WHERE m.chat_id = ? AND m.id > ?
     ORDER BY m.created_at ASC"
);
$stmt->execute([$chatId, $afterId]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar como leídos los mensajes del otro usuario
// ...

echo json_encode(['success' => true, 'messages' => $mensajes]);
```

**¿Por qué `after_id` y no la fecha?** Porque el ID es siempre único y ascendente. Si usáramos fechas, podría haber problemas si dos mensajes se crean en el mismo segundo.

## Los mensajes del sistema

Cuando cambia el estado de una transacción, se inserta automáticamente un mensaje del sistema:

```php
// Ejemplo: cuando se acepta una transacción
function insertarMensajeSistema($chatId, $texto, $conn) {
    $stmt = $conn->prepare(
        "INSERT INTO Mensajes (chat_id, usuario_id, contenido)
         VALUES (?, NULL, ?)"  // usuario_id = NULL → es del sistema
    );
    $stmt->execute([$chatId, $texto]);
}

// Se llama así:
insertarMensajeSistema($chatId, "✅ La transacción ha sido aceptada. El comprador ha elegido pagar por transferencia.");
```

En la vista, los mensajes con `usuario_id = NULL` se muestran centrados con fondo gris para distinguirlos.

## El chat bloqueado

Cuando la transacción está en `entregado` o `cancelada`, el chat se bloquea:
- El input de escribir desaparece
- Aparece un mensaje: "La transacción ha finalizado. Este chat está cerrado."
- El endpoint de enviar mensajes rechaza nuevos mensajes del estado de la transacción

## El contador de no leídos en el navbar

El navbar muestra un badge rojo con el número de mensajes no leídos. Se actualiza consultando `api/chat_unread_count.php`:

```php
// api/chat_unread_count.php
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM Mensajes m
     JOIN Chats c ON m.chat_id = c.id
     WHERE m.leido = 0
       AND m.usuario_id != ?           -- no contar los propios
       AND (c.comprador_id = ? OR c.vendedor_id = ?)"  -- solo mis chats
);
$stmt->execute([$userId, $userId, $userId]);
echo json_encode(['total' => $stmt->fetchColumn()]);
```

---

# 11. LAS TRANSACCIONES — LA MÁQUINA DE ESTADOS

## ¿Qué es una máquina de estados?

Una **máquina de estados** es un sistema donde algo puede estar en uno de varios "estados" y solo puede pasar de un estado a otro de forma controlada.

Piénsalo como un semáforo: solo puede estar en rojo, amarillo o verde. No puede pasar de rojo a verde directamente (siempre pasa por amarillo). Y no puede estar en dos colores a la vez.

En MercApp, las transacciones funcionan igual.

## Los 6 estados

```
┌─────────────────────────────────────────────────────────────┐
│                    ESTADOS DE TRANSACCIÓN                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  [pendiente] → [aceptada] → [pago_pendiente] → [enviado]   │
│                                                      ↓       │
│                                               [entregado ✓]  │
│                                                              │
│  Desde CUALQUIER estado → [cancelada ✗]                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### pendiente
- Estado inicial cuando el comprador abre un chat y pulsa "Proponer transacción"
- El vendedor ha visto la propuesta pero no ha hecho nada aún
- El comprador puede cancelar o esperar

### aceptada
- El **comprador** ha confirmado que quiere seguir adelante
- Ha indicado: método de pago, dirección de envío, notas, y en caso de trueque, qué producto ofrece
- El vendedor puede ver toda esta información

### pago_pendiente
- El **comprador** ha indicado que ya ha realizado el pago (transferencia, bizum, etc.)
- O bien: el pago con Stripe ya se ha verificado automáticamente
- El vendedor debe confirmar que ha recibido el dinero y preparar el envío

### enviado
- El **vendedor** ha preparado y enviado el producto
- Puede añadir un número de seguimiento
- El comprador debe esperar y confirmar cuando lo reciba

### entregado ✓
- El **comprador** confirma que ha recibido el producto en buen estado
- La transacción se cierra con éxito
- Se envía un email automático a ambas partes
- Aparece el modal de valoración

### cancelada ✗
- Cualquiera de los dos puede cancelar en cualquier momento
- El producto vuelve automáticamente al estado `activo`

## ¿Quién puede hacer cada cambio?

| Cambio de estado | ¿Quién lo hace? | ¿Qué aporta? |
|-----------------|----------------|-------------|
| pendiente → aceptada | Comprador | Método de pago, dirección, notas, producto ofrecido (si es trueque) |
| aceptada → pago_pendiente | Comprador | (indica que ya pagó) |
| pendiente → pago_pendiente | Comprador (solo con Stripe) | stripe_payment_intent_id |
| pago_pendiente → enviado | Vendedor | Número de seguimiento (opcional) |
| enviado → entregado | Comprador | (confirma que lo recibió) |
| cualquier → cancelada | Cualquiera | — |

## Cómo se implementa en el código

El modelo `Transaction.php` tiene el método principal `updateEstado()`:

```php
public function updateEstado($transaccionId, $nuevoEstado, $userId, $datosExtra = []) {

    // 1. Cargar la transacción actual de la BD
    $transaccion = $this->getById($transaccionId);

    // 2. Verificar que el usuario tiene permiso para esta transición
    $esComprador = ($transaccion['comprador_id'] == $userId);
    $esVendedor  = ($transaccion['vendedor_id']  == $userId);

    $transicionesPermitidas = [
        'pendiente'      => ['aceptada', 'cancelada'],
        'aceptada'       => ['pago_pendiente', 'cancelada'],
        'pago_pendiente' => ['enviado', 'cancelada'],
        'enviado'        => ['entregado', 'cancelada'],
    ];

    // ¿Es una transición válida?
    $estadoActual = $transaccion['estado'];
    if (!in_array($nuevoEstado, $transicionesPermitidas[$estadoActual] ?? [])) {
        throw new Exception("Transición no permitida: $estadoActual → $nuevoEstado");
    }

    // ¿Tiene permiso el usuario?
    $accionesComprador = ['aceptada', 'pago_pendiente', 'entregado', 'cancelada'];
    $accionesVendedor  = ['enviado', 'cancelada'];

    if (in_array($nuevoEstado, $accionesComprador) && !$esComprador) {
        throw new Exception("Solo el comprador puede hacer esta acción");
    }
    if ($nuevoEstado === 'enviado' && !$esVendedor) {
        throw new Exception("Solo el vendedor puede marcar como enviado");
    }

    // 3. Construir la consulta de actualización
    $campos = ['estado = ?'];
    $valores = [$nuevoEstado];

    // Guardar timestamp de la transición
    $timestampsCampos = [
        'aceptada'       => 'fecha_aceptacion',
        'pago_pendiente' => 'fecha_pago_confirmado',
        'enviado'        => 'fecha_envio',
        'entregado'      => 'fecha_entrega',
    ];
    if (isset($timestampsCampos[$nuevoEstado])) {
        $campos[]  = $timestampsCampos[$nuevoEstado] . ' = NOW()';
    }

    // Guardar datos extra (metodo_pago, direccion_envio, etc.)
    foreach ($datosExtra as $campo => $valor) {
        $campos[]  = "$campo = ?";
        $valores[] = $valor;
    }

    $valores[] = $transaccionId;
    $sql = "UPDATE Transacciones SET " . implode(', ', $campos) . " WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($valores);

    // 4. Acciones especiales según el nuevo estado
    if ($nuevoEstado === 'cancelada') {
        // El producto vuelve a estar activo
        $this->reactivarProducto($transaccion['producto_id']);
    }

    if ($nuevoEstado === 'entregado') {
        // Enviar email de confirmación a ambas partes
        $this->enviarEmailEntrega($transaccion);
    }
}
```

## El bloqueo del producto

Cuando existe una transacción activa (pendiente, aceptada o enviado), el producto queda bloqueado. Se comprueba en el modelo antes de permitir editar, eliminar o iniciar otra transacción:

```php
public function tieneTransaccionActiva($productoId): bool {
    $stmt = $this->conn->prepare(
        "SELECT COUNT(*) FROM Transacciones
         WHERE producto_id = ?
           AND estado IN ('pendiente', 'aceptada', 'pago_pendiente', 'enviado')"
    );
    $stmt->execute([$productoId]);
    return $stmt->fetchColumn() > 0;
}
```

---

# 12. EL TRUEQUE — CÓMO FUNCIONA EL INTERCAMBIO

## Tipos de transacción

MercApp soporta tres tipos:

1. **venta**: el comprador paga dinero y recibe el producto
2. **intercambio**: el comprador ofrece uno de sus propios productos a cambio (trueque puro, sin dinero)
3. **mixto**: el comprador ofrece un producto MÁS una cantidad de dinero extra para equilibrar el valor

## ¿Cómo ofrece el comprador su producto?

Cuando el comprador acepta una transacción de tipo `intercambio` o `mixto`:
1. Se le muestra un selector con sus productos activos (obtenidos de `api/mis_productos_activos.php`)
2. Selecciona el producto que quiere ofrecer
3. Al enviar el formulario, el `producto_ofrecido_id` se guarda en la tabla `Intercambio_Detalle`

```php
// api/mis_productos_activos.php
$stmt = $conn->prepare(
    "SELECT p.id, p.titulo, p.precio,
            (SELECT url FROM Imagenes_Producto
             WHERE producto_id = p.id AND orden = 1 LIMIT 1) AS imagen
     FROM Productos p
     WHERE p.usuario_id = ?
       AND p.estado = 'activo'"
);
$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetchAll());
```

## La tabla Intercambio_Detalle

Es muy simple: solo relaciona una transacción con el producto ofrecido:

```sql
INSERT INTO Intercambio_Detalle (transaccion_id, producto_ofrecido_id)
VALUES (42, 18);  -- En la transacción 42, el comprador ofrece el producto 18
```

## La visualización en el chat

Después de que la transacción pase a `aceptada`, el panel del chat muestra ambos productos en paralelo:

```
┌────────────────────┬────────────────────┐
│   PRODUCTO DEL     │   PRODUCTO QUE     │
│     VENDEDOR       │  OFRECE EL COMPRADOR│
│                    │                    │
│  [imagen]          │  [imagen]          │
│  Guitarra Yamaha   │  Cámara Canon      │
│  120€              │  150€              │
└────────────────────┴────────────────────┘
```

Esto permite a ambas partes ver claramente qué se intercambia.

---

# 13. STRIPE — PAGOS CON TARJETA

## ¿Qué es Stripe?

Stripe es una plataforma de pagos online. Permite cobrar con tarjeta de crédito de forma segura. MercApp lo usa para la opción de pago con tarjeta.

**Punto importante de seguridad:** Stripe se encarga de todo lo relacionado con la tarjeta. Nosotros **nunca** recibimos ni almacenamos el número de tarjeta del usuario. Stripe procesa todo eso.

## El flujo de pago paso a paso

### Paso 1 — El comprador elige Stripe
El comprador selecciona "Tarjeta · Stripe" en el formulario de aceptación de transacción.

### Paso 2 — El frontend carga Stripe.js
```html
<!-- Se carga la librería de Stripe desde sus propios servidores -->
<script src="https://js.stripe.com/v3/"></script>
```

```javascript
// Inicializamos Stripe con nuestra clave pública
const stripe = Stripe('pk_test_XXXXXXXXXX');

// Stripe crea un campo de tarjeta seguro (dentro de un iframe de Stripe)
const elementos = stripe.elements();
const campoTarjeta = elementos.create('card');
campoTarjeta.mount('#stripe-card-element'); // Lo insertamos en el HTML
```

El `CardElement` de Stripe es un campo especial que aparece en la página pero en realidad está dentro de un iframe de los servidores de Stripe. Los datos de la tarjeta nunca pasan por nuestro servidor.

### Paso 3 — El frontend pide al backend que cree el PaymentIntent
```javascript
// Cuando el usuario pulsa "Pagar"
const respuesta = await fetch('/api/stripe_create_payment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ transaccion_id: 42 })
});
const datos = await respuesta.json();
const clientSecret = datos.client_secret;
```

### Paso 4 — El backend crea el PaymentIntent
```php
// api/stripe_create_payment.php
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']); // clave privada

// Obtenemos el precio del producto
$transaccion = getTransaccion($transaccionId);
$importeEnCentimos = (int)($transaccion['precio_final'] * 100); // 50€ → 5000

// Creamos el PaymentIntent en los servidores de Stripe
$intent = \Stripe\PaymentIntent::create([
    'amount'   => $importeEnCentimos,
    'currency' => 'eur',
    'metadata' => ['transaccion_id' => $transaccionId]
]);

// Devolvemos el client_secret al frontend
echo json_encode(['client_secret' => $intent->client_secret]);
```

El **PaymentIntent** es básicamente una "intención de pago" en los servidores de Stripe. El `client_secret` es como un ticket que identifica ese pago específico.

### Paso 5 — El frontend confirma el pago con la tarjeta
```javascript
// Confirmamos el pago con la tarjeta del usuario
const resultado = await stripe.confirmCardPayment(clientSecret, {
    payment_method: {
        card: campoTarjeta
    }
});

if (resultado.error) {
    // Pago fallido → mostrar error
    mostrarError(resultado.error.message);
} else if (resultado.paymentIntent.status === 'succeeded') {
    // Pago exitoso → añadimos el ID al formulario y lo enviamos
    campoHiddenIntentId.value = resultado.paymentIntent.id;
    formulario.submit();
}
```

### Paso 6 — El backend verifica el pago de forma independiente
```php
// controllers/chat_update_transaction.php
// IMPORTANTE: nunca confiar solo en lo que dice el navegador
// Verificamos el PaymentIntent directamente con Stripe

$intentId = $_POST['stripe_payment_intent_id'];
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
$intent = \Stripe\PaymentIntent::retrieve($intentId);

if ($intent->status !== 'succeeded') {
    die('El pago no se ha completado correctamente');
}

// Todo correcto → avanzar transacción a pago_pendiente
$transaction->updateEstado($transaccionId, 'pago_pendiente', $userId, [
    'stripe_payment_intent_id' => $intentId,
    'metodo_pago' => 'stripe'
]);
```

**¿Por qué verificamos en el servidor?** Porque un usuario malintencionado podría modificar el JavaScript del navegador y enviar un `stripe_payment_intent_id` falso. Al verificarlo con la API de Stripe desde nuestro servidor (con la clave privada), garantizamos que el pago realmente ocurrió.

## El flujo Stripe salta el estado "aceptada"

Con Stripe, la transacción va directamente de `pendiente` a `pago_pendiente`, porque el pago ya está verificado. No necesita pasar por `aceptada` (que es cuando el comprador dice "voy a pagar" pero aún no lo ha hecho).

## Tarjeta de prueba

En modo test (que es como está configurado ahora), se puede pagar con:
- **Número:** `4242 4242 4242 4242`
- **Fecha:** cualquiera futura (ej: 12/29)
- **CVC:** cualquier número de 3 dígitos (ej: 123)

---

# 14. EL SISTEMA DE VALORACIONES

## ¿Cuándo se puede valorar?

Solo cuando la transacción llega al estado `entregado`. El sistema verifica esto en el backend antes de procesar cualquier valoración.

## Las tres dimensiones

A diferencia de plataformas como Wallapop (solo estrellas genéricas), MercApp valora en tres aspectos:

- **Fiabilidad** (1-5): ¿Cumplió lo que prometió? ¿El producto estaba en el estado descrito?
- **Comunicación** (1-5): ¿Respondió rápido? ¿Fue claro y amable?
- **Puntualidad** (1-5): ¿Fue puntual en la entrega o recogida?

## El modal automático

Cuando la transacción llega a `entregado`, aparece automáticamente un modal de valoración con un pequeño retardo:

```javascript
// Se espera 800ms para que el usuario vea el cambio de estado
// antes de que aparezca el modal
setTimeout(() => {
    const modal = bootstrap.Modal.getOrCreateInstance('#modalValorar');
    modal.show();
}, 800);
```

## Calcular la puntuación media

El perfil público de cada usuario muestra su puntuación media:

```sql
-- Se calcula la media de las tres dimensiones
SELECT
    AVG(fiabilidad)   AS media_fiabilidad,
    AVG(comunicacion) AS media_comunicacion,
    AVG(puntualidad)  AS media_puntualidad,
    AVG((fiabilidad + comunicacion + puntualidad) / 3) AS media_total,
    COUNT(*) AS total_valoraciones
FROM Valoraciones
WHERE evaluado_id = 42
```

---

# 15. EL SISTEMA DE NOTIFICACIONES

## ¿Para qué sirven?

Las notificaciones avisan al usuario cuando pasan cosas importantes:
- Le enviaron un mensaje
- Su transacción cambió de estado
- Alguien quiere comprar su producto

## Cómo se crean

Se insertan en la tabla `Notificaciones` automáticamente en los controladores:

```php
function crearNotificacion($userId, $tipo, $mensaje, $url, $conn) {
    $stmt = $conn->prepare(
        "INSERT INTO Notificaciones (usuario_id, tipo, mensaje, url)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $tipo, $mensaje, $url]);
}

// Ejemplo: notificar al vendedor cuando el comprador acepta
crearNotificacion(
    $vendedorId,
    'transaccion_aceptada',
    'Tu transacción ha sido aceptada por el comprador',
    "/chat?id={$chatId}",
    $conn
);
```

## El badge en el navbar

El navbar muestra el número de notificaciones no leídas. Se actualiza cada pocos segundos:

```javascript
function actualizarBadgeNotificaciones() {
    fetch('/api/notificaciones.php')
        .then(r => r.json())
        .then(datos => {
            const total = datos.data.length;
            if (total > 0) {
                badge.textContent = total;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        });
}
```

---

# 16. EL PANEL DE ADMINISTRACIÓN

## ¿Quién puede acceder?

Solo usuarios con `$_SESSION['role'] === 'admin'`. Todos los endpoints de admin verifican esto al inicio:

```php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}
```

Si alguien intenta acceder sin ser admin, recibe un error 403 y el script para inmediatamente.

## Funcionalidades del panel

### Gestión de usuarios
- Ver todos los usuarios con búsqueda por nombre o email
- Cambiar el rol de un usuario (hacer admin o quitar privilegios)
- Bloquear/desbloquear usuarios (`activo = 0/1`)

### Gestión de productos
- Ver todos los productos con filtro por estado
- Eliminar productos inapropiados

### Gestión de reportes
- Los usuarios pueden reportar productos que les parecen inapropiados
- El admin revisa los reportes y los marca como "revisado" o "descartado"

### Estadísticas
El panel muestra números globales del sistema:
```sql
SELECT
    (SELECT COUNT(*) FROM Usuarios WHERE activo = 1) AS usuarios_activos,
    (SELECT COUNT(*) FROM Productos WHERE estado = 'activo') AS productos_activos,
    (SELECT COUNT(*) FROM Transacciones WHERE estado = 'entregado') AS transacciones_completadas,
    (SELECT COUNT(*) FROM Reportes WHERE estado = 'pendiente') AS reportes_pendientes
```

### Exportación CSV

Los admins pueden descargar datos en CSV (formato que se abre con Excel):

```php
// api/admin_export_usuarios.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="usuarios_' . date('Y-m-d') . '.csv"');

$salida = fopen('php://output', 'w');

// Cabeceras del CSV
fputcsv($salida, ['ID', 'Nombre', 'Email', 'Rol', 'Verificado', 'Fecha Registro']);

// Datos
$stmt = $conn->query("SELECT id, nombre, email, rol, email_verificado, created_at FROM Usuarios");
while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($salida, $fila);
}
```

---

# 17. LA SEGURIDAD — CÓMO PROTEGEMOS LA APLICACIÓN

## Las amenazas más comunes en webs

### 1. SQL Injection — Inyección SQL

**¿Qué es?** Un ataque donde el usuario introduce código SQL en un formulario para manipular la base de datos.

**Ejemplo de ataque:**
```
En el campo de login, email: ' OR '1'='1
```
Si no hay protección, la consulta queda: `WHERE email = '' OR '1'='1'` y devuelve todos los usuarios.

**Nuestra protección:** Prepared statements PDO en absolutamente todas las consultas. El valor del usuario nunca se pega directamente en el SQL.

### 2. XSS — Cross-Site Scripting

**¿Qué es?** Un ataque donde alguien guarda código JavaScript malicioso en la base de datos y luego se ejecuta en el navegador de otros usuarios.

**Ejemplo de ataque:**
```
En el campo de nombre de producto: <script>alert('hackeado')</script>
```
Si no hay protección, ese script se ejecutaría en el navegador de cualquiera que vea el producto.

**Nuestra protección:**
- Twig escapa automáticamente todas las variables: `{{ variable }}` convierte `<` en `&lt;`, `>` en `&gt;`, etc.
- En PHP puro: `htmlspecialchars($variable)` hace lo mismo manualmente.

### 3. Fuerza bruta en el login

**¿Qué es?** Un programa automático prueba miles de contraseñas hasta dar con la correcta.

**Nuestra protección:** La tabla `LoginIntentos` registra cada intento fallido. Si hay más de 5 intentos desde la misma IP en 15 minutos, se bloquea temporalmente.

### 4. Contraseñas débiles almacenadas

**¿Qué es el problema?** Si guardamos las contraseñas en texto plano y la BD se hackea, el atacante tiene todas las contraseñas.

**Nuestra protección:** Usamos **bcrypt** a través de `password_hash()`. Características de bcrypt:
- Genera un resultado diferente cada vez aunque la contraseña sea la misma (gracias a la "sal" aleatoria)
- Es deliberadamente lento (para que un ataque de diccionario tarde años)
- Es unidireccional (no se puede "desencriptar")

```php
// Guardar contraseña
$hash = password_hash('mi_contraseña_123', PASSWORD_DEFAULT);
// Resultado: $2y$10$someRandomSalt.hashedPassword

// Verificar contraseña
$correcto = password_verify('mi_contraseña_123', $hashGuardado); // true
$correcto = password_verify('contraseña_incorrecta', $hashGuardado); // false
```

### 5. Credenciales expuestas en el código

**¿Qué es el problema?** Si ponemos las contraseñas directamente en el código PHP y el código se sube a GitHub, las contraseñas quedan expuestas.

**Nuestra protección:** El archivo `.env` contiene todas las credenciales y **no se sube a Git** (está en `.gitignore`). El código PHP las lee con:

```php
$_ENV['STRIPE_SECRET_KEY']
$_ENV['SMTP_PASSWORD']
$_ENV['DB_PASS']
```

---

# 18. LA API REST — LOS ENDPOINTS JSON

## ¿Qué es una API REST?

Una API REST es un conjunto de URLs que devuelven datos en formato JSON en vez de HTML. El navegador las llama con JavaScript (fetch) para obtener información sin recargar la página.

**Analogía:** Si la web normal es como un restaurante donde te traen el plato completo, la API es como la cocina que prepara ingredientes sueltos que el camarero combina.

## Formato de respuesta

Todos nuestros endpoints devuelven JSON con esta estructura:

```json
// Éxito:
{
  "success": true,
  "data": [...],
  "total": 42
}

// Error:
{
  "success": false,
  "error": "Descripción del error"
}
```

## Cómo se llama a una API desde el frontend

```javascript
// Ejemplo: buscar productos
fetch('/api/search_products.php?q=bicicleta&categoria=5&distancia_km=10&lat=37.38&lon=-5.97')
    .then(respuesta => respuesta.json())
    .then(datos => {
        if (datos.success) {
            datos.data.forEach(producto => mostrarProducto(producto));
        }
    })
    .catch(error => console.error('Error:', error));
```

## Todos los endpoints de MercApp

### Endpoints de Productos
| URL | Método | Para qué |
|-----|--------|---------|
| `/api/getProductsPaginated.php` | GET | Cargar más productos (scroll infinito en la home) |
| `/api/search_products.php` | GET | Búsqueda con todos los filtros |
| `/api/mis_productos_activos.php` | GET | Mis productos activos (para ofrecer en trueque) |
| `/api/get_filters.php` | GET | Obtener listas de categorías y estados |

### Endpoints de Chat
| URL | Método | Para qué |
|-----|--------|---------|
| `/api/chat_messages.php` | GET | Obtener mensajes nuevos (polling) |
| `/api/chat_unread_count.php` | GET | Cuántos mensajes no leídos tengo |
| `/api/chat_mark_all_read.php` | POST | Marcar mensajes como leídos |

### Endpoints de Transacciones
| URL | Método | Para qué |
|-----|--------|---------|
| `/controllers/chat_start_transaction.php` | POST | Iniciar una nueva transacción |
| `/controllers/chat_update_transaction.php` | POST | Cambiar el estado de una transacción |
| `/api/mis_transacciones.php` | GET | Ver mi historial de transacciones |
| `/api/stripe_create_payment.php` | POST | Crear un PaymentIntent en Stripe |

### Endpoints de Usuario
| URL | Método | Para qué |
|-----|--------|---------|
| `/api/notificaciones.php` | GET | Ver mis notificaciones |
| `/api/notificaciones.php` | POST | Marcar notificaciones como leídas |
| `/api/get_rating.php` | GET | Ver la puntuación de un usuario |
| `/api/normalize_address.php` | GET | Proxy de Nominatim para geocodificar |
| `/api/check_session.php` | GET | Comprobar si tengo sesión activa |

### Endpoints de Admin (solo admins)
| URL | Método | Para qué |
|-----|--------|---------|
| `/api/admin_users.php` | GET | Lista de usuarios |
| `/api/admin_products.php` | GET | Lista de productos |
| `/api/admin_reports.php` | GET | Lista de reportes |
| `/api/admin_export_usuarios.php` | GET | Descargar CSV de usuarios |
| `/api/admin_export_transacciones.php` | GET | Descargar CSV de transacciones |

---

# 19. LOS TESTS CON PHPUNIT

## ¿Qué es un test?

Un test es un trozo de código que comprueba automáticamente que otro trozo de código funciona correctamente. En vez de probar manualmente ("voy a intentar hacer login con una contraseña incorrecta y veo qué pasa"), el test lo hace automáticamente.

## ¿Por qué son importantes?

- Detectan errores antes de que lleguen al usuario
- Permiten hacer cambios con confianza (si el test sigue pasando, no rompiste nada)
- Documentan el comportamiento esperado del código

## PHPUnit

PHPUnit es el framework de tests estándar de PHP. Está en la carpeta `tests/prueba_unitaria/`.

## Estructura de un test

```php
class TransactionModelTest extends TestCase {

    private $transaction;

    // setUp() se ejecuta antes de cada test
    protected function setUp(): void {
        $conn = Database::getConnection();
        $this->transaction = new Transaction($conn);
    }

    // Cada método que empieza por "test" es un caso de prueba
    public function testTransicionValidaPendienteAceptada(): void {
        // ARRANGE (preparar)
        $transaccion = [
            'estado'       => 'pendiente',
            'comprador_id' => 1,
            'vendedor_id'  => 2
        ];

        // ACT (actuar)
        $puedeTransicionar = $this->transaction->validarTransicion(
            $transaccion,
            'aceptada',
            1,       // userId = 1 (el comprador)
            false    // no es el vendedor
        );

        // ASSERT (verificar)
        $this->assertTrue($puedeTransicionar);
    }

    public function testVendedorNoPuedeAceptar(): void {
        $transaccion = [
            'estado'       => 'pendiente',
            'comprador_id' => 1,
            'vendedor_id'  => 2
        ];

        // El vendedor (userId=2) NO debería poder aceptar
        $this->expectException(Exception::class);

        $this->transaction->validarTransicion($transaccion, 'aceptada', 2, true);
    }
}
```

## Cómo ejecutar los tests

```bash
# Ejecutar todos los tests con formato legible
vendor/bin/phpunit --testdox

# Ejecutar solo los tests de transacciones
vendor/bin/phpunit tests/prueba_unitaria/TransactionModelTest.php --testdox
```

El resultado se ve así:
```
Transaction Model (Tests\TransactionModelTest)
 ✔ Transición válida pendiente a aceptada
 ✔ Vendedor no puede aceptar
 ✔ Cancelación disponible desde cualquier estado
 ✔ Producto vuelve a activo al cancelar
```

---

# 20. EL MODO OSCURO

## ¿Cómo funciona?

El modo oscuro se activa añadiendo el atributo `data-theme="dark"` al elemento `<html>` del documento. Los estilos CSS usan ese atributo para cambiar colores:

```css
/* Colores por defecto (modo claro) */
:root {
    --c-bg: #ffffff;
    --c-text: #212529;
    --c-card: #f8f9fa;
}

/* Colores en modo oscuro */
[data-theme="dark"] {
    --c-bg: #1a1a2e;
    --c-text: #e0e0e0;
    --c-card: #16213e;
}

/* Los elementos usan variables, no colores fijos */
body {
    background-color: var(--c-bg);
    color: var(--c-text);
}
```

## El JavaScript del botón

```javascript
// Leer preferencia guardada al cargar la página
const temaGuardado = localStorage.getItem('tema') ?? 'light';
document.documentElement.setAttribute('data-theme', temaGuardado);

// Al hacer clic en el botón de cambiar tema
botonTema.addEventListener('click', () => {
    const temaActual = document.documentElement.getAttribute('data-theme');
    const nuevoTema = temaActual === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-theme', nuevoTema);
    localStorage.setItem('tema', nuevoTema); // Guardar para la próxima visita
});
```

**¿Por qué `data-theme` en `<html>` y no en `<body>`?** Porque así afecta también a elementos fuera del body, y es más compatible con diferentes navegadores y extensiones.

**¿Qué es `localStorage`?** Es una zona de almacenamiento del navegador donde podemos guardar pequeños datos sin necesidad de servidor. La preferencia del tema se guarda ahí para que se recuerde aunque cierres el navegador.

---

# 21. VARIABLES DE ENTORNO — EL ARCHIVO .ENV

## ¿Qué son las variables de entorno?

Son configuraciones que cambian según el entorno (desarrollo local vs. producción) y que **no deben estar en el código**. Por ejemplo, las contraseñas, las claves de API y los datos de conexión a la BD.

## El archivo .env

```ini
# Configuración de la base de datos
DB_HOST=localhost
DB_NAME=mercapp
DB_USER=root
DB_PASS=

# Configuración del email (SMTP Gmail)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=mercapp.noreply@gmail.com
SMTP_PASS=contraseña_de_aplicacion_gmail

# Claves de Stripe
STRIPE_PUBLIC_KEY=pk_test_51...
STRIPE_SECRET_KEY=sk_test_51...

# URL base de la aplicación
APP_URL=http://localhost/MercApp
```

## ¿Cómo se carga?

En `config/bootstrap.php`:

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
// Ahora $_ENV['DB_PASS'] tiene el valor del .env
```

## ¿Por qué no se sube a Git?

Porque si subieras las contraseñas a GitHub (repositorio público), cualquiera podría verlas y acceder a tu base de datos, tu cuenta de email o tu cuenta de Stripe.

El archivo `.gitignore` tiene la línea `.env` para que Git lo ignore completamente.

---

# 22. COMPOSER — EL GESTOR DE DEPENDENCIAS

## ¿Qué es Composer?

Composer es el gestor de dependencias de PHP. Una **dependencia** es código que hemos escrito otras personas y que nosotros usamos en nuestro proyecto. En vez de copiar ese código manualmente, Composer lo descarga y gestiona automáticamente.

Es similar a npm (para JavaScript) o pip (para Python).

## El archivo composer.json

```json
{
    "require": {
        "twig/twig": "^3.0",
        "vlucas/phpdotenv": "^5.0",
        "stripe/stripe-php": "^12.0",
        "phpmailer/phpmailer": "^6.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    }
}
```

## Librerías que usamos

| Librería | Para qué |
|----------|---------|
| `twig/twig` | Motor de plantillas para las vistas |
| `vlucas/phpdotenv` | Cargar el archivo .env |
| `stripe/stripe-php` | SDK oficial de Stripe para procesar pagos |
| `phpmailer/phpmailer` | Enviar emails por SMTP |
| `phpunit/phpunit` | Framework de tests (solo en desarrollo) |

## Instalar las dependencias

```bash
# Descarga todas las librerías definidas en composer.json
composer install
```

Esto crea la carpeta `vendor/` con todo el código de las librerías. Esta carpeta **no se sube a Git** (está en .gitignore) porque pesa mucho y cualquiera puede descargarla con `composer install`.

---

# 23. PREGUNTAS QUE PUEDE HACER EL TRIBUNAL — CON RESPUESTAS COMPLETAS

---

**¿Por qué elegisteis PHP y no otro lenguaje?**

> PHP es el lenguaje que hemos estudiado durante el curso y se integra de forma nativa con Apache y MySQL, que son los servidores que usamos. Para el tamaño de este proyecto, PHP es perfectamente adecuado y tiene una comunidad enorme con mucha documentación. Además, plataformas masivas como WordPress, Facebook (en sus inicios) o Wikipedia están hechas en PHP.

---

**¿Por qué no usasteis un framework como Laravel o Symfony?**

> Decidimos usar PHP nativo para entender bien los fundamentos: cómo funciona PDO, cómo se gestionan las sesiones, cómo se hace el enrutamiento. Con Laravel muchas cosas quedan "ocultas" detrás de la magia del framework y es más difícil entender qué está pasando realmente. Para un proyecto académico donde queremos demostrar que entendemos PHP, el enfoque nativo tiene más sentido.

---

**¿Qué es un prepared statement y por qué lo usáis?**

> Un prepared statement es una forma segura de ejecutar consultas SQL. En vez de pegar directamente los datos del usuario en la consulta (lo que permitiría ataques de SQL injection), usamos marcadores `?` y PDO se encarga de sanitizar los datos automáticamente. Por ejemplo, si alguien intentara introducir código SQL en el formulario de búsqueda, PDO lo trataría como texto plano, no como código SQL.

---

**¿Cómo guardáis las contraseñas?**

> Las contraseñas nunca se guardan en texto plano. Usamos la función `password_hash()` de PHP con el algoritmo bcrypt. Bcrypt tiene tres características importantes: incluye una "sal" aleatoria (por eso el hash es diferente cada vez aunque la contraseña sea la misma), es deliberadamente lento (para dificultar ataques de diccionario) y es unidireccional (no se puede revertir). Para verificar, usamos `password_verify()`.

---

**¿El chat es realmente en tiempo real?**

> Técnicamente no es "tiempo real" puro como WhatsApp, que usa WebSockets. Nosotros usamos polling: el navegador pregunta al servidor cada 3 segundos si hay mensajes nuevos. Para el caso de uso de MercApp (dos personas negociando, no un chat masivo), un retardo de hasta 3 segundos es imperceptible en la práctica. Los WebSockets habrían requerido una configuración de servidor más compleja.

---

**¿Cómo funciona Stripe? ¿Guardáis los datos de las tarjetas?**

> No guardamos ningún dato de tarjeta. Stripe se encarga de todo. El flujo es: el usuario introduce la tarjeta en un campo que en realidad está en los servidores de Stripe (no en el nuestro). Stripe nos da un "client_secret" que usamos para confirmar el pago. Cuando el pago se confirma, Stripe nos da un "PaymentIntent ID" que verificamos desde nuestro servidor con la API de Stripe antes de avanzar la transacción. Así nos aseguramos de que el pago realmente ocurrió, sin depender solo de lo que dice el navegador.

---

**¿Cómo evitáis que dos usuarios compren el mismo producto a la vez?**

> Implementamos la regla de "una sola transacción activa por producto". Antes de crear una nueva transacción, comprobamos si existe alguna en estado pendiente, aceptada, pago_pendiente o enviado para ese producto. Si existe, se devuelve un error. Esto es una comprobación en el backend, así que aunque dos usuarios pulsaran "comprar" al mismo tiempo, la base de datos solo aceptaría la primera transacción.

---

**¿Por qué usáis Nominatim y no Google Maps?**

> Google Maps tiene un coste a partir de cierto número de peticiones (es gratis hasta 200$ al mes de uso, pero luego cobra). Nominatim, que es el servicio de geocodificación de OpenStreetMap, es completamente gratuito y no requiere tarjeta de crédito ni API key. Para un proyecto académico sin ingresos, es la opción más adecuada. La única limitación es que hay que respetar el límite de 1 petición por segundo, que hemos implementado en el frontend.

---

**¿Qué es la fórmula Haversine?**

> Es una fórmula matemática que calcula la distancia en kilómetros entre dos puntos en la superficie de la Tierra, dados en coordenadas de latitud y longitud. La usamos directamente en SQL para filtrar los productos que están dentro del radio de búsqueda que indica el usuario. El número 6371 que aparece en la fórmula es el radio de la Tierra en kilómetros.

---

**¿Qué hace el panel de administración?**

> El admin puede gestionar usuarios (ver todos, cambiar roles, bloquear cuentas), gestionar productos (ver todos, eliminar inapropiados), revisar reportes que hacen los usuarios sobre productos que consideran inapropiados, ver estadísticas globales del sistema y exportar datos en formato CSV para analizarlos en Excel.

---

**¿Cómo funciona la máquina de estados de las transacciones?**

> Una transacción puede estar en uno de 6 estados: pendiente, aceptada, pago_pendiente, enviado, entregado o cancelada. El sistema valida en el backend que cada cambio de estado sea válido (no puedes pasar de pendiente a entregado directamente) y que el usuario que hace el cambio tenga permiso (el vendedor no puede confirmar que ha recibido el pago, eso solo lo puede hacer el comprador). Cada cambio de estado también genera un mensaje automático en el chat para que ambas partes estén informadas.

---

**¿Por qué tenéis una tabla Intercambio_Detalle separada y no un campo en Transacciones?**

> Porque en las transacciones de tipo "mixto" o "intercambio", el comprador ofrece uno de sus productos. Si pusiéramos solo un campo `producto_ofrecido_id` en Transacciones, quedaría NULL para todas las ventas normales, lo cual es un diseño de base de datos poco limpio. Tener una tabla separada es más normalizado y además permite en el futuro ampliar para soportar múltiples productos ofrecidos sin cambiar la estructura principal.

---

**¿Para qué sirve Twig si ya tenéis PHP?**

> PHP mezclado con HTML puede volverse muy difícil de leer y mantener. Twig separa la lógica de la presentación con una sintaxis mucho más limpia. Además, Twig aplica escape automático a todas las variables, lo que previene ataques XSS sin que tengamos que acordarnos de hacer `htmlspecialchars()` en cada salida. Y la herencia de plantillas de Twig nos permite mantener el navbar y el footer en un solo archivo que todas las páginas comparten.

---

**¿Qué son las migraciones SQL y para qué sirven?**

> Cuando el proyecto ya está en producción (o en la base de datos de alguien) y necesitas añadir nuevas columnas o tablas, no puedes simplemente ejecutar el `bd.sql` completo porque borraría todos los datos. Las migraciones son scripts SQL pequeños que solo hacen los cambios nuevos. Usamos `ADD COLUMN IF NOT EXISTS` para que sean idempotentes (se pueden ejecutar varias veces sin error). Por ejemplo, cuando añadimos Stripe, creamos una migración que solo añade la columna `stripe_payment_intent_id`.

---

**¿Qué hace exactamente Composer?**

> Composer es como un catálogo de tienda de libros: le dices qué librerías necesitas (en el archivo composer.json), él las descarga de internet, gestiona las versiones para que sean compatibles entre sí y genera un autoloader que permite usar esas librerías en el código con un simple `use`. Sin Composer tendríamos que descargar manualmente cada librería y gestionar las dependencias a mano.

---

**¿Cómo probáis que el código funciona?**

> Usamos PHPUnit 11, el framework de tests estándar de PHP. Los tests prueban principalmente el modelo de transacciones, que es la parte más crítica de la lógica de negocio. Comprobamos que las transiciones de estado son correctas, que los actores tienen los permisos correctos y que los datos se guardan bien en la BD. Para ejecutarlos: `vendor/bin/phpunit --testdox`.

---

*Fin de la guía técnica. Si alguien del tribunal pregunta algo que no está aquí, la respuesta siempre puede ser: "Esa funcionalidad específica no la hemos implementado en esta versión, pero lo tendríamos en cuenta como mejora futura."*
