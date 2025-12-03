<?php
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
        $password_encripted = password_hash($password, PASSWORD_DEFAULT);

        try {
            // ===============================
            // CONEXIÓN A LA BASE DE DATOS
            // ===============================
            $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");

            // ===============================
            // COMPROBAR SI EL EMAIL YA ESTÁ REGISTRADO
            // ===============================
            $consulta = $bd->prepare("SELECT * FROM usuario WHERE email=?");
            $consulta->execute([$email]);

            if ($consulta->rowCount() == 1) {
                echo "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
                exit;
            }

            // ===============================
            // INSERTAR NUEVO USUARIO
            // ===============================
            $consulta = $bd->prepare("INSERT INTO usuario(email,contraseña_hash,nombre) VALUES (?,?,?)");
            $consulta->execute([$email, $password_encripted, $name]);

            if ($consulta->rowCount() === 1) {
                // ===============================
                // GENERAR TOKEN DE VERIFICACIÓN Y ACTUALIZAR EN BD
                // ===============================
                $verifyToken = bin2hex(random_bytes(32));
                $upd = $bd->prepare("UPDATE usuario SET verify_token = ? WHERE email = ?");
                $upd->execute([$verifyToken, $email]);

                // ===============================
                // ENVIAR EMAIL DE VERIFICACIÓN
                // ===============================
                require __DIR__ . '/../../config/mail_config.php';
                $verifyUrl = "http://localhost/MercApp/public/views/verify_email.php?token={$verifyToken}&email=" . urlencode($email);
                $subject = "Confirma tu correo en MercaAPP";
                $body = "Bienvenido {$name}, confirma tu correo: {$verifyUrl}";

                sendMail($email, $name, $subject, $body);

                // ===============================
                // MENSAJE DE ÉXITO Y REDIRECCIÓN
                // ===============================
                echo "<div class='alert alert-success'>
                        Registro correcto. Revisa tu correo para confirmar.
                      </div>";

                echo "<script>
                        setTimeout(function() {
                          window.location.href = 'pending_verification.php';
                        }, 2000);
                      </script>";
                exit;
            }

        } catch (Exception $e) {
            // ===============================
            // MANEJO DE ERRORES DE BASE DE DATOS
            // ===============================
            echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
            exit;
        }
    }
}
