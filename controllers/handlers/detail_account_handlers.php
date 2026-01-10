<?php
// RUTA CORREGIDA para db.php
require_once __DIR__ . '/../../config/db.php';

/**
 * Script para la edición de los datos del usuario logueado.
 * 
 * Permite modificar nombre, apellidos, correo, teléfono y foto de perfil.
 * Realiza validaciones básicas y maneja la subida de imágenes.
 */

// ===============================
// INICIO DE SESIÓN Y CONTROL DE ACCESO
// ===============================
session_start();

// Si el usuario no está logueado, redirigir al login
if (!isset($_SESSION["user_id"])) {
    header("location: auth/login.php"); // RUTA CORREGIDA
    exit;
}

try {
    // ===============================
    // CONEXIÓN A LA BASE DE DATOS
    // ===============================
    $bd = new Database();
    $bd = $bd->getConnection();
    
    $userId = $_SESSION["user_id"];

    // ===============================
    // OBTENER DATOS DEL USUARIO
    // ===============================
    $stmt = $bd->prepare(
        "SELECT nombre, apellidos, email, telefono, foto_perfil 
         FROM usuario 
         WHERE id = ?"
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra el usuario, mostrar error y salir
    if (!$user) {
        echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
        exit;
    }

    // ===============================
    // PROCESAMIENTO DE FORMULARIO POST
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener y limpiar datos del formulario
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');

        // ===============================
        // VALIDACIONES BÁSICAS
        // ===============================
       if (empty($nombre) || empty($email)) {
    $error = "Nombre y correo son obligatorios.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "El correo no es válido.";
} else {

    // ===============================
    // MANEJO DE FOTO DE PERFIL (OPTIMIZADO)
    // ===============================
    $foto = $user['foto_perfil'];

    if (!empty($_FILES['foto']['name'])) {

        $targetDir = "../../uploads/users/";

        // Crear carpeta si no existe
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validar que realmente es una imagen
        $info = getimagesize($_FILES['foto']['tmp_name']);
        if ($info === false) {
            $error = "El archivo subido no es una imagen válida.";
        } else {

            $mime = $info['mime'];

            // Cargar imagen según tipo
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                case 'image/pjpeg':
                    $img = imagecreatefromjpeg($_FILES['foto']['tmp_name']);
                    break;

                case 'image/png':
                    $img = imagecreatefrompng($_FILES['foto']['tmp_name']);
                    break;

                case 'image/gif':
                    $img = imagecreatefromgif($_FILES['foto']['tmp_name']);
                    break;

                case 'image/webp':
                    $img = imagecreatefromwebp($_FILES['foto']['tmp_name']);
                    break;

                default:
                    $error = "Formato de imagen no soportado.";
                    $img = null;
            }

            if ($img) {

                // ===============================
                // REDIMENSIONAR (máx 500px)
                // ===============================
                $maxWidth = 500;
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

                // ===============================
                // GUARDAR COMO WEBP OPTIMIZADO
                // ===============================
                $fileName = "FotoPerfil_" . $userId  . ".webp";
                $targetFile = $targetDir . $fileName;

                imagewebp($img, $targetFile, 80);

                // Guardar ruta relativa para BD
                $foto = "uploads/users/" . $fileName;

                // Actualizar sesión para mostrar la nueva foto
                $_SESSION["profile_photo"] = $foto;
            }
        }
    }

    



            // ===============================
            // ACTUALIZAR DATOS EN LA BASE DE DATOS
            // ===============================
            $upd = $bd->prepare(
                "UPDATE usuario 
                 SET nombre=?, apellidos=?, email=?, telefono=?, foto_perfil=? 
                 WHERE id=?"
            );
            $upd->execute([$nombre, $apellidos, $email, $telefono, $foto, $userId]);

            // Redirigir a la página de detalles con flag de actualización
            header("Location: detail_account.php?updated=1");
            exit;
        }
    }
} catch (Exception $e) {
    // Manejo de errores de base de datos
    echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>
