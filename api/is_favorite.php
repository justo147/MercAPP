<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo "0";
    exit;
}

$usuario_id = $_SESSION["user_id"];
$producto_id = $_GET["producto_id"] ?? null;

if (!$producto_id) {
    echo "0";
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT COUNT(*) FROM Favoritos 
            WHERE usuario_id = :usuario_id AND producto_id = :producto_id";

    // AQUÍ ESTABA EL ERROR
    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':producto_id' => $producto_id
    ]);

    echo $stmt->fetchColumn() > 0 ? "1" : "0";

} catch (Exception $e) {
    echo "0";
}
