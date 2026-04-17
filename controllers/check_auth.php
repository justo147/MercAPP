<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/bootstrap.php';

// Si no hay sesión iniciada, lo mandamos al login
if (!isset($_SESSION["user_id"])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $userModel = new User($conn);
    
    // Consultamos el estado real del usuario en la BD
    $usuarioReal = $userModel->getById($_SESSION["user_id"]);
    
    // Si el usuario ya no existe o su estado no es 'activo'
    if (!$usuarioReal || $usuarioReal['estado'] !== 'activo') {
        
        // 1. Vaciamos las variables de sesión
        session_unset();
        // 2. Destruimos la sesión por completo
        session_destroy();
        
        // 3. Lo echamos al login con una etiqueta de error en la URL
        header("Location: {$BASE}/public/views/auth/login.php?error=cuenta_inactiva");
        exit;
    }

    // Un pequeño bonus: si le das permisos de admin a alguien mientras está conectado, 
    // esto actualizará su sesión al instante para que le aparezca el botón del panel.
    $_SESSION['role'] = $usuarioReal['rol'];

} catch (Exception $e) {
    // Si falla la conexión, por seguridad detenemos la carga
    die("Error crítico validando la sesión.");
}