<?php
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../models/Product.php';

    $db = new Database();
    $conn = $db->getConnection();
    $productModel = new Product($conn);

    $productId = intval($_POST["id"] ?? 0);

    // Verificar que el producto pertenece al usuario logueado
    if (session_status() === PHP_SESSION_NONE) {
    session_start();}
    $userId = $_SESSION["user_id"] ?? 0;

    $stmt = $conn->prepare("SELECT usuario_id FROM Productos WHERE id = ?");
    $stmt->execute([$productId]);
    $owner = $stmt->fetchColumn();

    if (!$owner) {
        $error = "El producto no existe";
        return;
    }

    if ((int)$owner !== (int)$userId) {
        $error = "No tienes permiso para editar este producto";
        return;
    }



    if ($productId <= 0) {
        $error = "ID de producto inválido";
        return;
    }

    // ============================
    // VALIDAR CAMPOS
    // ============================
    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $precio = floatval($_POST["precio"] ?? 0);
    $categoria_id = intval($_POST["categoria_id"] ?? 0);
    $estado_producto_id = intval($_POST["estado_producto_id"] ?? 0);
    $tipo_transaccion = trim($_POST["tipo_transaccion"] ?? "");
    $ubicacion = trim($_POST["ubicacion"] ?? "");

    if ($titulo === "" || $categoria_id === 0 || $estado_producto_id === 0) {
        $error = "Faltan campos obligatorios";
        return;
    }

    // ============================
    // ACTUALIZAR PRODUCTO
    // ============================
    $updateData = [
        "categoria_id" => $categoria_id,
        "titulo" => $titulo,
        "descripcion" => $descripcion,
        "precio" => $precio,
        "estado_producto_id" => $estado_producto_id,
        "tipo_transaccion" => $tipo_transaccion,
        "estado_publicacion_id" => 1,
        "ubicacion" => $ubicacion
    ];

    if (!$productModel->update($productId, $updateData)) {
        $error = "Error al actualizar el producto";
        return;
    }

    // ============================
    // ELIMINAR IMÁGENES MARCADAS
    // ============================
    if (!empty($_POST["delete_images"])) {
        foreach ($_POST["delete_images"] as $url) {

            // borrar archivo físico
            $ruta = __DIR__ . "/../../" . $url;
            if (file_exists($ruta)) unlink($ruta);

            // borrar de BD
            $productModel->deleteImage($productId, $url);
        }
    }

    // ============================
    // SUBIR NUEVAS IMÁGENES (GD)
    // ============================
    if (!empty($_FILES["imagenes"]["name"][0])) {

        $uploadDir = __DIR__ . "/../../uploads/products/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Orden enviado desde JS
        $ordenes = explode(",", $_POST["orden_imagenes"]);
        $ordenes = array_map("intval", $ordenes);

        foreach ($ordenes as $orden => $originalIndex) {

            if (!isset($_FILES["imagenes"]["tmp_name"][$originalIndex])) continue;

            $tmp = $_FILES["imagenes"]["tmp_name"][$originalIndex];

            // Validar imagen
            $info = getimagesize($tmp);
            if ($info === false) continue;

            $mime = $info["mime"];

            switch ($mime) {
                case "image/jpeg":
                case "image/jpg":
                case "image/pjpeg":
                    $img = imagecreatefromjpeg($tmp);
                    break;

                case "image/png":
                    $img = imagecreatefrompng($tmp);
                    break;

                case "image/gif":
                    $img = imagecreatefromgif($tmp);
                    break;

                case "image/webp":
                    $img = imagecreatefromwebp($tmp);
                    break;

                default:
                    continue 2;
            }

            // Redimensionar a máx 1200px
            $maxWidth = 1200;
            $w = imagesx($img);
            $h = imagesy($img);

            if ($w > $maxWidth) {
                $ratio = $h / $w;
                $newW = $maxWidth;
                $newH = $maxWidth * $ratio;

                $tmpImg = imagecreatetruecolor($newW, $newH);
                imagecopyresampled($tmpImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                $img = $tmpImg;
            }

            // Guardar como WEBP optimizado
            $fileName = "Prod_{$productId}_" . uniqid() . ".webp";
            $rutaFinal = $uploadDir . $fileName;

            imagewebp($img, $rutaFinal, 80);

            // Guardar en BD
            $productModel->insertImage(
                $productId,
                "uploads/products/" . $fileName,
                $orden
            );
        }
    }

    $success = "Producto actualizado correctamente";
}
