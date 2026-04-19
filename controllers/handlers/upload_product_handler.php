<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Notification.php';

session_start();

// Si no está logueado → fuera
if (!isset($_SESSION["user_id"])) {
    header("location: auth/login.php");
    exit;
}

try {
    // ===============================
    // CONEXIÓN BD
    // ===============================
    $bd = new Database();
    $bd = $bd->getConnection();

    $userId = $_SESSION["user_id"];

    // ===============================
    // CARGAR CATEGORÍAS
    // ===============================
    $stmt = $bd->query("SELECT id, nombre FROM Categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===============================
    // CARGAR ESTADOS DE PRODUCTO
    // ===============================
    $stmt = $bd->query("SELECT id, nombre FROM EstadoProducto ORDER BY id");
    $estados_producto = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===============================
    // PROCESAR FORMULARIO
    // ===============================
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        // Sanitizar
        $titulo      = trim($_POST["titulo"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio      = trim($_POST["precio"] ?? "");
        $categoria   = intval($_POST["categoria_id"] ?? 0);
        $estadoProd  = intval($_POST["estado_producto_id"] ?? 0);
        $tipoTrans   = trim($_POST["tipo_transaccion"] ?? "");
        $ubicacion   = trim($_POST["ubicacion"] ?? "");
        $lat         = is_numeric($_POST["lat"] ?? '') ? floatval($_POST["lat"]) : null;
        $lon         = is_numeric($_POST["lon"] ?? '') ? floatval($_POST["lon"]) : null;

        // ===============================
        // VALIDACIONES
        // ===============================
        if (empty($titulo)) {
            $error = "El título es obligatorio.";
        } elseif ($categoria <= 0) {
            $error = "Debes seleccionar una categoría.";
        } elseif ($estadoProd <= 0) {
            $error = "Debes seleccionar un estado del producto.";
        } elseif (!in_array($tipoTrans, ["venta", "intercambio", "mixto"])) {
            $error = "Tipo de transacción no válido.";
        }

        if (!empty($error)) {
            return;
        }

        // ===============================
        // INSERTAR PRODUCTO
        // ===============================
        $stmt = $bd->prepare("
            INSERT INTO Productos
            (usuario_id, categoria_id, titulo, descripcion, precio, estado_producto_id, tipo_transaccion, estado_publicacion_id, ubicacion, lat, lon)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $categoria,
            $titulo,
            $descripcion,
            $precio !== "" ? $precio : null,
            $estadoProd,
            $tipoTrans,
            $ubicacion,
            $lat,
            $lon,
        ]);

        $productoId = $bd->lastInsertId();

        // ===============================
        // ORDEN DE IMÁGENES
        // ===============================
        $ordenImagenes = $_POST["orden_imagenes"] ?? "";
        $ordenArray = array_map('intval', explode(",", $ordenImagenes));

        // ===============================
        // MANEJO DE IMÁGENES
        // ===============================
        if (!empty($_FILES["imagenes"]["name"][0])) {

            $targetDir =__DIR__."/../../uploads/products/";

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            foreach ($ordenArray as $pos => $originalIndex) {

                $tmpName = $_FILES["imagenes"]["tmp_name"][$originalIndex];
                $name = $_FILES["imagenes"]["name"][$originalIndex];

                if (empty($name)) continue;

                // Validar imagen
                $info = getimagesize($tmpName);
                if ($info === false) {
                    continue;
                }

                $mime = $info["mime"];

                // Cargar según tipo
                switch ($mime) {
                    case 'image/jpeg':
                    case 'image/jpg':
                    case 'image/pjpeg':
                        $img = imagecreatefromjpeg($tmpName);
                        break;

                    case 'image/png':
                        $img = imagecreatefrompng($tmpName);
                        break;

                    case 'image/gif':
                        $img = imagecreatefromgif($tmpName);
                        break;

                    case 'image/webp':
                        $img = imagecreatefromwebp($tmpName);
                        break;

                    default:
                        $img = null;
                }

                if (!$img) continue;

                // Redimensionar si es muy grande
                $maxWidth = 900;
                $width = imagesx($img);
                $height = imagesy($img);

                if ($width > $maxWidth) {
                    $ratio = $height / $width;
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth * $ratio;

                    $tmp = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    $img = $tmp;
                }

                // Guardar como WEBP
                $fileName = "Prod_" . $productoId . "_" . uniqid() . ".webp";
                $targetFile = $targetDir . $fileName;

                imagewebp($img, $targetFile, 80);

                // Ruta relativa para BD
                $rutaBD = "uploads/products/" . $fileName;

                // Insertar en tabla Imagenes_prod
                $stmtImg = $bd->prepare("
                    INSERT INTO Imagenes_prod (id_producto, url, orden)
                    VALUES (?, ?, ?)
                ");
                $stmtImg->execute([$productoId, $rutaBD, $pos + 1]);
            }
        }

        // ===============================
        // NOTIFICAR COINCIDENCIAS EN DESEOS
        // ===============================
        // Buscar usuarios que tienen deseos que coinciden con este producto.
        // Se usa la misma lógica de keywords que api/deseos.php.
        try {
            $stmtDeseos = $bd->query(
                "SELECT d.id, d.usuario_id, d.etiquetas, d.categoria_id, d.estado_producto_id
                 FROM Deseos d
                 WHERE d.usuario_id != {$userId}"   // no notificarse a uno mismo
            );
            $todosDeseos = $stmtDeseos->fetchAll(PDO::FETCH_ASSOC);

            $notifModel    = new Notification($bd);
            $notificados   = [];   // evitar duplicados por usuario

            foreach ($todosDeseos as $deseo) {
                $destinatario = intval($deseo['usuario_id']);
                if (in_array($destinatario, $notificados)) continue;

                // Comprobar categoría (si el deseo la especifica)
                if (!empty($deseo['categoria_id']) && intval($deseo['categoria_id']) !== $categoria) continue;

                // Comprobar estado del producto (si el deseo lo especifica)
                if (!empty($deseo['estado_producto_id']) && intval($deseo['estado_producto_id']) !== $estadoProd) continue;

                // Comprobar keywords en título y descripción
                $palabras = array_filter(
                    array_map('trim', preg_split('/[\s,;]+/', $deseo['etiquetas'] ?? '')),
                    fn($p) => mb_strlen($p) >= 2
                );
                if (empty($palabras)) continue;

                $hayCoincidencia = false;
                foreach ($palabras as $p) {
                    if (mb_stripos($titulo, $p) !== false || mb_stripos($descripcion, $p) !== false) {
                        $hayCoincidencia = true;
                        break;
                    }
                }

                if ($hayCoincidencia) {
                    $notifModel->create(
                        $destinatario,
                        'coincidencia',
                        "¡Hay un nuevo producto que coincide con tu deseo \"{$deseo['etiquetas']}\": {$titulo}."
                    );
                    $notificados[] = $destinatario;
                }
            }
        } catch (Exception $eNotif) {
            // Fallo silencioso — no interrumpe la publicación
        }

        // ===============================
        // REDIRIGIR CON ÉXITO
        // ===============================
        header("Location: upload_product.php?success=1");
        exit;
    }

} catch (Exception $e) {
    $error = "Error en la base de datos: " . htmlspecialchars($e->getMessage());
}
?>
