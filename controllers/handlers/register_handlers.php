<?php
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/RateLimiter.php';
require_once __DIR__ . '/../../config/db.php';
/**
 * Script de registro de usuario.
 *
 * Funcionalidad:
 * - Verifica si ya existe una sesión activa y redirige al home.
 * - Valida los datos enviados por POST: nombre, email, contraseña y confirmación.
 * - Comprueba que la contraseña cumpla requisitos y que el email no esté registrado.
 * - Inserta el usuario en la base de datos con contraseña encriptada.
 * - Genera un token de verificación por correo y envía el email.
 */

session_start();

// ===============================
// CONTROL DE ACCESO
// ===============================
// Si ya existe una sesión activa (usuario logueado), redirigir al home
if (isset($_SESSION["user_id"])) {
    header("location: ../home.php");
    exit;
}

// ===============================
// PROCESAMIENTO DEL FORMULARIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Rate limiting por IP
    $db_rl   = new Database();
    $rl      = new RateLimiter($db_rl->getConnection());
    $ip      = RateLimiter::getClientIp();

    if ($rl->estaBloqueado($ip)) {
        $min = ceil($rl->segundosRestantes($ip) / 60);
        echo "<div class='alert alert-danger'>Demasiados intentos. Espera {$min} minuto(s) antes de volver a registrarte.</div>";
        exit;
    }

    // ===============================
    // VERIFICACIÓN HCAPTCHA
    // ===============================
    $hcaptchaSecret   = $_ENV['HCAPTCHA_SECRET'] ?? '0x0000000000000000000000000000000000000000'; // clave test
    $hcaptchaResponse = trim($_POST['h-captcha-response'] ?? '');

    if (empty($hcaptchaResponse)) {
        echo "<div class='alert alert-danger'>Por favor, completa el captcha.</div>";
        exit;
    }

    $verifyData = http_build_query(['secret' => $hcaptchaSecret, 'response' => $hcaptchaResponse]);
    $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'content' => $verifyData, 'timeout' => 5, 'ignore_errors' => true]];
    $verifyResult = @file_get_contents('https://api.hcaptcha.com/siteverify', false, stream_context_create($opts));
    $verifyJson   = $verifyResult ? json_decode($verifyResult, true) : null;

    if (!$verifyJson || empty($verifyJson['success'])) {
        echo "<div class='alert alert-danger'>Verificación de captcha fallida. Inténtalo de nuevo.</div>";
        exit;
    }

    // ===============================
    // VALIDACIONES DE CAMPOS
    // ===============================
    if (empty($_POST["name"]) || empty($_POST["password"]) || empty($_POST["confirmPass"]) || empty($_POST["email"])) {
        echo "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
        exit;
    } elseif (strlen($_POST["password"]) < 8) {
        echo "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
        exit;
    } elseif ($_POST["password"] !== $_POST["confirmPass"]) {
        echo "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
        exit;
    } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>El correo tiene que ser válido.</div>";
        exit;
    } else {
        // ===============================
        // ASIGNACIÓN DE VARIABLES Y ENCRIPTACIÓN DE CONTRASEÑA
        // ===============================
        $name = $_POST["name"];
        $password = $_POST["password"];
        $email = $_POST["email"];

        try {
            // ===============================
            // CONEXIÓN A LA BASE DE DATOS
            // ===============================
            $db = new Database();
	    $conn = $db->getConnection();
            $userModel = new User($conn);
            // ===============================
            // COMPROBAR SI EL EMAIL YA ESTÁ REGISTRADO
            // ===============================
            if ($userModel->emailExists($email)) {
                $rl->registrarIntento($ip, $email);
                echo "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
                exit;
            }

            // ===============================
            // INSERTAR NUEVO USUARIO
            // ===============================
            $userModel->create($email, $password, $name);

            // ===============================
            // GENERAR TOKEN DE VERIFICACIÓN Y ACTUALIZAR EN BD
            // ===============================
            $verifyToken = bin2hex(random_bytes(32));
            $userModel->setVerifyToken($email, $verifyToken);


            // ===============================
            // ENVIAR EMAIL DE VERIFICACIÓN
            // ===============================
            require __DIR__ . '/../../config/mail_config.php';
            $verifyUrl = "{$BASE}/public/views/verify_email.php?token={$verifyToken}&email=" . urlencode($email);
            $subject = "Confirma tu correo en MercaAPP";
            $body = "Bienvenido {$name}, confirma tu correo: {$verifyUrl}";

            sendMail($email, $name, $subject, $body);

            // ===============================
            // MENSAJE DE ÉXITO Y REDIRECCIÓN
            // ===============================
            // Redirigimos de vuelta a register.php con parámetros para el BOM
            echo "REGISTRO_EXITOSO";
            exit;
        } catch (Exception $e) {
            // ===============================
            // MANEJO DE ERRORES DE BASE DE DATOS
            // ===============================
            echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
            exit;
        }
    }
}
