<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Comprobar sesión
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

    $sql = "INSERT INTO Favoritos (usuario_id, producto_id, fecha_guardado)
            VALUES (:usuario_id, :producto_id, NOW())";

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
