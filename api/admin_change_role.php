<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado."]);
    exit;
}

$data      = json_decode(file_get_contents("php://input"), true);
$target_id = isset($data['id'])  ? intval($data['id'])  : 0;
$new_role  = isset($data['rol']) ? trim($data['rol'])    : '';

$valid_roles = ['registrado', 'admin'];

if ($target_id <= 0 || !in_array($new_role, $valid_roles)) {
    echo json_encode(["success" => false, "error" => "Datos inválidos."]);
    exit;
}

if ($target_id === (int) $_SESSION['user_id']) {
    echo json_encode(["success" => false, "error" => "No puedes cambiar tu propio rol."]);
    exit;
}

try {
    $db        = new Database();
    $conn      = $db->getConnection();
    $userModel = new User($conn);

    $result = $userModel->changeRole($target_id, $new_role);

    if ($result) {
        echo json_encode(["success" => true, "message" => "Rol actualizado correctamente."]);
    } else {
        echo json_encode(["success" => false, "error" => "No se pudo actualizar el rol."]);
    }

} catch (Exception $e) {
    error_log("admin_change_role error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}
