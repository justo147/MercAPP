<?php
// No iniciar sesión aquí si ya lo haces en los handlers
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// 1. RUTA CORREGIDA para db.php
require_once __DIR__ . '/../config/db.php';

class AuthController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return "Por favor, complete todos los campos.";
        }

        try {
            $query = "SELECT * FROM usuario WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $row['contraseña_hash'])) {
                    
                    // Comprobar si la cuenta está verificada
                    if ($row['email_verificado'] != 1) {
                        return "Su cuenta aún no está verificada. Por favor, revise su correo electrónico.";
                    }

                    // Login exitoso
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION["profile_photo"] = $row["foto_perfil"];
                    $_SESSION["name"] = $row["nombre"];
                    
                    // 2. RUTA CORREGIDA para home.php
                    header("Location: ../home.php");
                    exit();
                } else {
                    return "Contraseña incorrecta.";
                }
            } else {
                return "No existe una cuenta con ese correo electrónico.";
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return "Ocurrió un error en el sistema. Intente más tarde.";
        }
    }
}
?>
