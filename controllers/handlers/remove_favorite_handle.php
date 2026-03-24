<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo "no_session";
    exit;
}

$usuario_id = $_SESSION["user_id"];
$producto_id = $_POST["producto_id"] ?? null;

if (!$producto_id) {
    echo "no_product";
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "DELETE FROM Favoritos 
            WHERE usuario_id = :usuario_id AND producto_id = :producto_id";

    // AQUÍ ESTABA EL ERROR
    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':producto_id' => $producto_id
    ]);

    echo "ok";

} catch (Exception $e) {
    echo "error";
}
