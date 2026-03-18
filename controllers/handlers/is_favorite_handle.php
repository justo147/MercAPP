<?php
session_start();

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
    $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT COUNT(*) FROM favoritos 
            WHERE usuario_id = :usuario_id AND producto_id = :producto_id";

    $stmt = $bd->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':producto_id' => $producto_id
    ]);

    echo $stmt->fetchColumn() > 0 ? "1" : "0";

} catch (Exception $e) {
    echo "0";
}
