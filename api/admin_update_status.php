<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

// 1. PROTECCIÓN ESTRICTA: Solo admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado. Se requieren permisos de administrador."]);
    exit;
}

// 2. Leer los datos enviados por JS (esperamos un JSON)
$data = json_decode(file_get_contents("php://input"), true);
$target_id = isset($data['id']) ? intval($data['id']) : 0;
$new_status = isset($data['estado']) ? trim($data['estado']) : '';

// Validar que el estado sea uno de los permitidos en tu base de datos
$valid_statuses = ['activo', 'suspendido', 'eliminado'];

if ($target_id <= 0 || !in_array($new_status, $valid_statuses)) {
    echo json_encode(["success" => false, "error" => "Datos inválidos o estado no permitido."]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $userModel = new User($conn);

    // 3. SEGUNDA CAPA DE SEGURIDAD: Comprobar a quién intentamos modificar
    $targetUser = $userModel->getById($target_id);
    
    if (!$targetUser) {
        echo json_encode(["success" => false, "error" => "El usuario no existe."]);
        exit;
    }

    if ($targetUser['rol'] === 'admin') {
        // ¡Cazado! Alguien intentó modificar a un admin. Lo bloqueamos.
        echo json_encode(["success" => false, "error" => "Operación rechazada: No puedes modificar el estado de un administrador."]);
        exit;
    }

    // 4. Ejecutar el cambio de estado usando tu método existente
    $updated = $userModel->changeStatus($target_id, $new_status);

    if ($updated) {
        echo json_encode(["success" => true, "message" => "Estado actualizado correctamente."]);
    } else {
        echo json_encode(["success" => false, "error" => "No se pudo actualizar el estado en la base de datos."]);
    }

} catch (Exception $e) {
    error_log("admin_update_status error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}