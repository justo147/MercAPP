<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(["success" => false, "error" => "Falta el parámetro id o no es válido"]);
    exit;
}

$userId     = intval($_GET['id']);
$esOwner    = isset($_SESSION['user_id']) && intval($_SESSION['user_id']) === $userId;

$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
$page   = isset($_GET['page'])  ? intval($_GET['page'])  : 1;
$offset = ($page - 1) * $limit;
$q      = isset($_GET['q'])     ? trim($_GET['q'])        : "";

// Filtro de estado: el propietario puede filtrar; visitantes solo ven activos
$estadosPermitidos = $esOwner ? ['activo', 'pausado', 'vendido'] : ['activo'];
$estadoFiltro      = trim($_GET['estado'] ?? 'todos');

if ($estadoFiltro !== 'todos' && in_array($estadoFiltro, $estadosPermitidos)) {
    $estados = [$estadoFiltro];
} else {
    $estados = $estadosPermitidos;
}

try {
    $db           = new Database();
    $conn         = $db->getConnection();
    $productModel = new Product($conn);

    $productos = $productModel->getByUserPaginated($userId, $limit, $offset, $q, $estados);
    $total     = $productModel->countByUser($userId, $q, $estados);

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
