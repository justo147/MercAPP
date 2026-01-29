<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/db.php';

class AuthController {

    private $conn;
    private $userModel;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->userModel = new User($this->conn);
    }

    public function login($email, $password) {

        // Validación básica
        if (empty($email) || empty($password)) {
            return "Por favor, complete todos los campos.";
        }

        // 1. Buscar usuario por email
        $user = $this->userModel->getByEmail($email);

        if (!$user) {
            return "No existe una cuenta con ese correo electrónico.";
        }

        // 2. Verificar contraseña
        if (!password_verify($password, $user['contraseña_hash'])) {
            return "Contraseña incorrecta.";
        }

        // 3. Verificar si el email está confirmado
        if ($user['email_verificado'] != 1) {
            return "Su cuenta aún no está verificada. Por favor, revise su correo electrónico.";
        }

        // 4. Verificar estado del usuario
        if ($user['estado'] !== 'activo') {
            return "Tu cuenta está suspendida o eliminada.";
        }

        // 5. Crear sesión
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['nombre'];
        $_SESSION['profile_photo'] = $user['foto_perfil'];
        $_SESSION['role'] = $user['rol'];

        // 6. Redirigir al home
        header("Location: ../home.php");
        exit;
    }
}
