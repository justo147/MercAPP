<?php
// RUTA CORREGIDA
require_once __DIR__ . '/../../config/db.php';

$token = $_GET['token'] ?? '';

$database = new Database();
$pdo = $database->getConnection();
$stmt = $pdo->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("El enlace no es válido o ha caducado.");
}

 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         if (empty($_POST["password"])) {
        echo "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
        exit;
    } elseif (strlen($_POST["password"]) < 8) {
        echo "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
        exit;
    }else {
        
        $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuario SET contraseña_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$newPass, $user['id']]);
        echo "<p class='sucess-mesage'>Contraseña actualizada correctamente.</p> <a href='auth/login.php' class='btn btn-primary'>Inicia sesión</a>";
    }
    }
