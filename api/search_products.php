<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

$db = new Database();
$conn = $db->getConnection();
$productModel = new Product($conn);

// Recoger filtros desde GET
$filters = [
    "q" => $_GET["q"] ?? "",
    "categoria" => $_GET["categoria"] ?? null,
    "estado_producto" => $_GET["estado_producto"] ?? null,
    "tipo_transaccion" => $_GET["tipo_transaccion"] ?? null,
    "precio_min" => $_GET["precio_min"] ?? null,
    "precio_max" => $_GET["precio_max"] ?? null,
    "ubicacion" => $_GET["ubicacion"] ?? null,
    "orden" => $_GET["orden"] ?? "fecha_desc",
    "limit" => $_GET["limit"] ?? 12,
    "offset" => $_GET["offset"] ?? 0
];

try {
    $productos = $productModel->search($filters);

    echo json_encode([
        "success" => true,
        "data" => $productos
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
