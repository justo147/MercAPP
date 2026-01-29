<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../AuthController.php';

$error_message = "";

// Si ya hay sesión, redirigir
if (isset($_SESSION["user_id"])) {
    header("Location: ../home.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = "Por favor, complete todos los campos.";
    } else {
        $auth = new AuthController();
        $result = $auth->login($email, $password);

        if (is_string($result)) {
            // Error devuelto por el controlador
            $error_message = $result;
        }
        // Si el login es correcto, el controlador redirige automáticamente
    }
}
