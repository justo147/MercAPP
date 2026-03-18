<?php
session_start();
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

$seguidor = $_POST['seguidor'] ?? null;
$seguido  = $_POST['seguido'] ?? null;

if (!$seguidor || !$seguido) {
    echo json_encode(["success" => false, "error" => "Datos inválidos"]);
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
