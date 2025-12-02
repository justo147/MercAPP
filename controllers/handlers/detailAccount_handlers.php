<?php
session_start(); // AÑADIR ESTO

require_once __DIR__ . '/../../controllers/AuthController.php';

$error_message = "";

// PRIMERO: Verificar si ya está logueado
if (isset($_SESSION["user_id"])) {
    header("Location: ../home.php");
    exit();
}

// SEGUNDO: Procesar login solo si no está logueado
if (!empty($_POST['email']) && !empty($_POST['password'])) {
    $auth = new AuthController();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->login($email, $password);

    if (is_string($result)) {
        $error_message = $result;
    } else {
        // Redirigir después de login exitoso
        if (isset($_SESSION["user_id"])) {
            header("Location: ../home.php");
            exit();
        }
    }
}
?>