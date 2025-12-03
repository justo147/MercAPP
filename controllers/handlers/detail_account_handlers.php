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
            // MANEJO DE FOTO DE PERFIL
            // ===============================
            $foto = $user['foto_perfil'];
            if (!empty($_FILES['foto']['name'])) {
                $targetDir = "../../uploads/";
                
                // Crear carpeta si no existe
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                // Obtener extensión del archivo en minúsculas
                $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                // Extensiones permitidas
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $allowed)) {
                    $error = "Formato de imagen no permitido.";
                } else {
                    // Crear nombre único para la imagen
                    $fileName = "FotoPerfil" . $userId . "." . $extension;
                    $targetFile = $targetDir . $fileName;

                    // Mover archivo subido a la carpeta de destino
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                        $foto = $targetFile;
                    }

                    // Guardar la ruta en sesión para mostrar la nueva foto inmediatamente
                    $_SESSION["profile_photo"] = $targetFile;
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
