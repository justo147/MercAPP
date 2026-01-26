<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../models/Product.php';

$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 12;
$offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0;

$producto = new Product();
$data = $producto->getPaginated($limit, $offset);

echo json_encode([
    "success" => true,
    "data" => $data
]);
