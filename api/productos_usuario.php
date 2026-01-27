<?php
session_start();
require_once '../models/Product.php';

header('Content-Type: application/json; charset=utf-8');

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(["error" => "Falta el parámetro id o no es válido"]);
    exit;
}

$userId = intval($_GET['id']);

// Parámetros de paginación
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
$page  = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$productModel = new Product();

// Obtener productos paginados
$productos = $productModel->getByUserPaginated($userId, $limit, $offset);

// Obtener total de productos del usuario
$total = $productModel->countByUser($userId);

echo json_encode([
    "productos" => $productos,
    "total" => $total,
    "page" => $page,
    "limit" => $limit
]);
