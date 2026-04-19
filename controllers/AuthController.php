<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/RateLimiter.php';
require_once __DIR__ . '/../config/db.php';

class AuthController {

    private $conn;
    private $userModel;
    private RateLimiter $rateLimiter;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->userModel   = new User($this->conn);
        $this->rateLimiter = new RateLimiter($this->conn);
    }

    public function login($email, $password) {

        if (empty($email) || empty($password)) {
            return "Por favor, complete todos los campos.";
        }

        $ip = RateLimiter::getClientIp();

        // Bloqueo por exceso de intentos
        if ($this->rateLimiter->estaBloqueado($ip)) {
            $min = ceil($this->rateLimiter->segundosRestantes($ip) / 60);
            return "Demasiados intentos fallidos. Inténtalo de nuevo en {$min} minuto(s).";
        }

        $user = $this->userModel->getByEmail($email);

        if (!$user) {
            $this->rateLimiter->registrarIntento($ip, $email);
            return "No existe una cuenta con ese correo electrónico.";
        }

        if (!password_verify($password, $user['contraseña_hash'])) {
            $this->rateLimiter->registrarIntento($ip, $email);
            return "Contraseña incorrecta.";
        }

        if ($user['email_verificado'] != 1) {
            return "Su cuenta aún no está verificada. Por favor, revise su correo electrónico.";
        }

        if ($user['estado'] !== 'activo') {
            return "Tu cuenta está suspendida o eliminada.";
        }

        // Login correcto: limpiar intentos y crear sesión
        $this->rateLimiter->limpiarIntentos($ip);
        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['name']          = $user['nombre'];
        $_SESSION['profile_photo'] = $user['foto_perfil'];
        $_SESSION['role']          = $user['rol'];

        header("Location: ../home.php");
        exit;
    }
}
