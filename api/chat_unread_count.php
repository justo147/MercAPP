<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    echo 0;
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Message.php';


$db = new Database();
$conn = $db->getConnection();

$mensajeModel = new Message($conn);

$usuarioActual = intval($_SESSION["user_id"]);
echo $mensajeModel->countAllUnread($usuarioActual);
