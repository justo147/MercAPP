<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(["success" => false, "error" => "Falta el parámetro id o no es válido"]);
    exit;
}

$userId = intval($_GET['id']);

// Parámetros de paginación
$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Parámetro de búsqueda
$q = isset($_GET['q']) ? trim($_GET['q']) : "";

try {
    // Conexión a la BD
    $db = new Database();
    $conn = $db->getConnection();

    // Modelo
    $productModel = new Product($conn);

    // Obtener productos paginados con búsqueda
    $productos = $productModel->getByUserPaginated($userId, $limit, $offset, $q);

    // Obtener total filtrado
    $total = $productModel->countByUser($userId, $q);

    echo json_encode([
        "success"   => true,
        "productos" => $productos,
        "total"     => $total,
        "page"      => $page,
        "limit"     => $limit
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error"   => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
