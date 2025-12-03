<?php
// RUTA CORREGIDA para db.php
require_once __DIR__ . '/../../config/db.php';

// ===============================
// INICIO DE SESIÓN Y CONTROL DE ACCESO
// ===============================
session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: auth/login.php"); // RUTA CORREGIDA
    exit;
}

try {
    // ===============================
    // CONEXIÓN A LA BASE DE DATOS
    // ===============================
    $bd = new Database();
    $bd=$bd->getConnection();
    
    $userId = $_SESSION["user_id"];

    // Obtener datos del usuario
    $stmt = $bd->prepare("SELECT nombre, apellidos, email, telefono, foto_perfil FROM usuario WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
        exit;
    }

    // ===============================
    // PROCESAMIENTO DE FORMULARIO POST
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre   = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        // Validaciones básicas
        if (empty($nombre) || empty($email)) {
            $error = "Nombre y correo son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo no es válido.";
        } else {
            // Manejo de foto
            $foto = $user['foto_perfil'];
            if (!empty($_FILES['foto']['name'])) {
                $targetDir = "../../uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                // Obtener extensión del archivo
                $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                // Validar extensión permitida
                $allowed = ['jpg', 'jpeg', 'png', 'gif','webp'];
                if (!in_array($extension, $allowed)) {
                    $error = "Formato de imagen no permitido.";
                } else {
                    // Crear nombre único con time() y sufijo FotoPerfil[idUsuario]
                    $fileName ="FotoPerfil" . $userId . "." . $extension;
                    $targetFile = $targetDir . $fileName;

                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                        $foto = $targetFile;
                    }
                    $_SESSION["profile_photo"]=$targetFile;
                }
            }

            // Actualizar datos
            $upd = $bd->prepare("UPDATE usuario SET nombre=?, apellidos=?, email=?, telefono=?, foto_perfil=? WHERE id=?");
            $upd->execute([$nombre, $apellidos, $email, $telefono, $foto, $userId]);

            header("Location: detail_account.php?updated=1");
            exit;
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>