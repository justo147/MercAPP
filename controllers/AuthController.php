<?php
/**
 * Controlador de autenticación de usuarios.
 *
 * Clase AuthController:
 * - Gestiona la conexión a la base de datos.
 * - Permite el inicio de sesión de usuarios.
 * - Verifica la contraseña y el estado de verificación de email.
 */
 
// No iniciar sesión aquí si ya lo haces en los handlers
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// 1. RUTA CORREGIDA para db.php
require_once __DIR__ . '/../config/db.php';

class AuthController {
    /**
     * @var Database Instancia de la clase Database
     */
    private $db;

    /**
     * @var PDO Conexión PDO a la base de datos
     */
    private $conn;

    /**
     * Constructor.
     * Inicializa la conexión a la base de datos.
     */
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Inicia sesión para un usuario dado email y contraseña.
     *
     * @param string $email Correo electrónico del usuario
     * @param string $password Contraseña en texto plano
     * @return string|null Retorna un mensaje de error en caso de fallo; redirige a home.php si el login es exitoso
     */
    public function login($email, $password) {
        // Validación de campos vacíos
        if (empty($email) || empty($password)) {
            return "Por favor, complete todos los campos.";
        }

        try {
            // Preparar y ejecutar consulta
            $query = "SELECT * FROM usuario WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar contraseña
                if (password_verify($password, $row['contraseña_hash'])) {
                    
                    // Comprobar si la cuenta está verificada
                    if ($row['email_verificado'] != 1) {
                        return "Su cuenta aún no está verificada. Por favor, revise su correo electrónico.";
                    }

                    // ===============================
                    // LOGIN EXITOSO: Crear sesión
                    // ===============================
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
            // Registrar error en logs
            error_log("Error en login: " . $e->getMessage());
            return "Ocurrió un error en el sistema. Intente más tarde.";
        }
    }
}
?>
