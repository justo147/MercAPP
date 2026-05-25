<?php

require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller
{
    public function login(array $params = []): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect("{$this->base}/home");
        }

        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../models/RateLimiter.php';

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']    ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $error = "Por favor, complete todos los campos.";
            } else {
                $userModel   = new User($this->conn);
                $rateLimiter = new RateLimiter($this->conn);
                $ip          = RateLimiter::getClientIp();

                if ($rateLimiter->estaBloqueado($ip)) {
                    $min   = ceil($rateLimiter->segundosRestantes($ip) / 60);
                    $error = "Demasiados intentos fallidos. Inténtalo de nuevo en {$min} minuto(s).";
                } else {
                    $user = $userModel->getByEmail($email);

                    if (!$user) {
                        $rateLimiter->registrarIntento($ip, $email);
                        $error = "No existe una cuenta con ese correo electrónico.";
                    } elseif (!password_verify($password, $user['contraseña_hash'])) {
                        $rateLimiter->registrarIntento($ip, $email);
                        $error = "Contraseña incorrecta.";
                    } elseif ($user['email_verificado'] != 1) {
                        $error = "Su cuenta aún no está verificada. Por favor, revise su correo electrónico.";
                    } elseif ($user['estado'] !== 'activo') {
                        $error = "Tu cuenta está suspendida o eliminada.";
                    } else {
                        $rateLimiter->limpiarIntentos($ip);
                        session_regenerate_id(true);
                        $_SESSION['user_id']       = $user['id'];
                        $_SESSION['email']         = $user['email'];
                        $_SESSION['name']          = $user['nombre'];
                        $_SESSION['profile_photo'] = $user['foto_perfil'];
                        $_SESSION['role']          = $user['rol'];
                        $this->redirect("{$this->base}/home");
                    }
                }
            }
        }

        $this->render('auth/login.html.twig', [
            'error_message' => $error ?: null,
            'post_email'    => $_POST['email'] ?? '',
        ]);
    }

    public function register(array $params = []): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect("{$this->base}/home");
        }

        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../models/RateLimiter.php';
        require_once __DIR__ . '/../config/bootstrap.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rateLimiter = new RateLimiter($this->conn);
            $ip          = RateLimiter::getClientIp();

            if ($rateLimiter->estaBloqueado($ip)) {
                $min = ceil($rateLimiter->segundosRestantes($ip) / 60);
                echo "<div class='alert alert-danger'>Demasiados intentos. Espera {$min} minuto(s) antes de volver a registrarte.</div>";
                exit;
            }

            $hcaptchaSecret   = $_ENV['HCAPTCHA_SECRET'] ?? '0x0000000000000000000000000000000000000000';
            $hcaptchaResponse = trim($_POST['h-captcha-response'] ?? '');

            if (empty($hcaptchaResponse)) {
                echo "<div class='alert alert-danger'>Por favor, completa el captcha.</div>";
                exit;
            }

            $verifyData   = http_build_query(['secret' => $hcaptchaSecret, 'response' => $hcaptchaResponse]);
            $opts         = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $verifyData, 'timeout' => 5, 'ignore_errors' => true]];
            $verifyResult = @file_get_contents('https://api.hcaptcha.com/siteverify', false, stream_context_create($opts));
            $verifyJson   = $verifyResult ? json_decode($verifyResult, true) : null;

            if (!$verifyJson || empty($verifyJson['success'])) {
                echo "<div class='alert alert-danger'>Verificación de captcha fallida. Inténtalo de nuevo.</div>";
                exit;
            }

            if (empty($_POST['name']) || empty($_POST['password']) || empty($_POST['confirmPass']) || empty($_POST['email'])) {
                echo "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
                exit;
            } elseif (strlen($_POST['password']) < 8) {
                echo "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
                exit;
            } elseif ($_POST['password'] !== $_POST['confirmPass']) {
                echo "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
                exit;
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                echo "<div class='alert alert-danger'>El correo tiene que ser válido.</div>";
                exit;
            }

            try {
                $userModel = new User($this->conn);

                if ($userModel->emailExists($_POST['email'])) {
                    $rateLimiter->registrarIntento($ip, $_POST['email']);
                    echo "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
                    exit;
                }

                $userModel->create($_POST['email'], $_POST['password'], $_POST['name']);

                $verifyToken = bin2hex(random_bytes(32));
                $userModel->setVerifyToken($_POST['email'], $verifyToken);

                require __DIR__ . '/../config/mail_config.php';
                require_once __DIR__ . '/../config/mail_templates.php';

                $verifyUrl = "{$this->base}/verify-email?token={$verifyToken}&email=" . urlencode($_POST['email']);
                sendMail($_POST['email'], $_POST['name'], "Confirma tu cuenta en MercApp", mailVerificacion($_POST['name'], $verifyUrl));

                echo "REGISTRO_EXITOSO";
                exit;
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
                exit;
            }
        }

        $this->render('auth/register.html.twig', []);
    }

    public function logout(array $params = []): void
    {
        session_unset();
        session_destroy();
        $this->redirect("{$this->base}/login");
    }

    public function pendingVerification(array $params = []): void
    {
        $this->render('auth/pending_verification.html.twig', [
            'pending_email' => $_SESSION['pending_email'] ?? null,
        ]);
    }

    public function verifyEmail(array $params = []): void
    {
        require_once __DIR__ . '/../models/User.php';

        $token  = $_GET['token'] ?? '';
        $email  = $_GET['email'] ?? '';
        $estado = 'Procesando...';

        try {
            $userModel = new User($this->conn);
            $u         = $userModel->getByEmail($email);

            if (!$u) {
                $estado = "Enlace inválido.";
            } elseif ((int) $u['email_verificado'] === 1) {
                $estado = "Este correo ya está verificado.";
            } elseif ($u['verify_token'] && hash_equals($u['verify_token'], $token)) {
                $userModel->verifyEmail($email, $token);
                $estado = "Correo verificado correctamente. Ya puedes iniciar sesión.";
            } else {
                $estado = "Token incorrecto.";
            }
        } catch (Exception $e) {
            error_log("Error en verificación: " . $e->getMessage());
            $estado = "Error en el servidor.";
        }

        $this->render('verify_email.html.twig', ['estado' => $estado]);
    }

    public function forgotPassword(array $params = []): void
    {
        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../config/bootstrap.php';

        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email     = trim($_POST['email'] ?? '');
            $userModel = new User($this->conn);
            $user      = $userModel->getByEmail($email);

            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", time() + 3600);
                $userModel->setResetToken($email, $token, $expires);

                $resetLink     = "{$this->base}/reset-password?token={$token}&email=" . urlencode($email);
                $nombreUsuario = $user['nombre'] ?? '';

                require __DIR__ . '/../config/mail_config.php';
                require_once __DIR__ . '/../config/mail_templates.php';
                sendMail($email, $nombreUsuario, 'Recuperar contraseña — MercApp', mailRecuperarContrasena($nombreUsuario, $resetLink));

                $mensaje = "Hemos enviado un enlace de recuperación a tu correo.";
            } else {
                $mensaje = "No existe ninguna cuenta con ese correo.";
            }
        }

        $this->render('forgot_pass.html.twig', ['mensaje' => $mensaje]);
    }

    public function resetPassword(array $params = []): void
    {
        require_once __DIR__ . '/../models/User.php';

        $token   = $_GET['token'] ?? '';
        $email   = $_GET['email'] ?? '';
        $message = null;

        $userModel = new User($this->conn);
        $user      = $userModel->validateResetToken($email, $token);

        if (!$user) {
            echo "El enlace no es válido o ha caducado.";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['password'])) {
                $message = "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
            } elseif (strlen($_POST['password']) < 8) {
                $message = "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
            } else {
                $userModel->updatePassword($email, $_POST['password']);
                $message = "<div class='alert alert-success d-flex flex-column align-items-start'>
                    <p class='mb-2'>Contraseña actualizada correctamente.</p>
                    <a href='{$this->base}/login' class='btn btn-primary'>Inicia sesión</a>
                </div>";
            }
        }

        $this->render('reset_password.html.twig', [
            'message' => $message,
            'token'   => $token,
        ]);
    }
}
