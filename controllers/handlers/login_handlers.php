<?php
// Siempre iniciar la sesión antes de trabajar con $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta corregida: desde /handlers/ basta con subir un nivel para llegar a /controllers/
require_once __DIR__ . '/../AuthController.php';

$error_message = "";

// Comprobamos que se envió email y password
if (!empty($_POST['email']) && !empty($_POST['password'])) {
    $auth = new AuthController();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->login($email, $password);

    // Si login retorna un string, es un mensaje de error
    if (is_string($result)) {
        $error_message = $result;
    }
}

// Si la sesión ya está iniciada, redirigimos al home
if (isset($_SESSION["user_id"])) {
    // Ruta relativa correcta desde /handlers/ hasta /public/views/home.php
    header("Location: ../home.php");
    exit;
}
