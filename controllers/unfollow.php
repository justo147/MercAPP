<?php
session_start();
require_once "../models/User.php";
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$seguidor = $_POST['seguidor'] ?? null;
$seguido = $_POST['seguido'] ?? null;

$db = new Database();
$conn = $db->getConnection();
$usuarioPDO = new User($conn);
$ok = $usuarioPDO->dejarDeSeguir($seguidor, $seguido);

echo json_encode(["success" => $ok]);
