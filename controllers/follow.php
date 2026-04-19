<?php
session_start();
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

$seguidor = intval($_POST['seguidor'] ?? 0);
$seguido  = intval($_POST['seguido']  ?? 0);

if (!$seguidor || !$seguido) {
    echo json_encode(["success" => false, "error" => "Datos inválidos"]);
    exit;
}

// Un usuario no puede seguirse a sí mismo
if ($seguidor === $seguido) {
    echo json_encode(["success" => false, "error" => "No puedes seguirte a ti mismo"]);
    exit;
}

// Verificar que el seguidor es el usuario en sesión (evitar IDOR)
if ($seguidor !== intval($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No autorizado"]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$usuarioPDO = new User($conn);

try {
    $ok = $usuarioPDO->seguirUsuario($seguidor, $seguido);

    echo json_encode([
        "success" => $ok,
        "debug" => [
            "seguidor" => $seguidor,
            "seguido" => $seguido
        ]
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
