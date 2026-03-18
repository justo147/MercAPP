<?php
session_start();

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
    $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "DELETE FROM favoritos 
            WHERE usuario_id = :usuario_id AND producto_id = :producto_id";

    $stmt = $bd->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':producto_id' => $producto_id
    ]);

    echo "ok";

} catch (Exception $e) {
    echo "error";
}
