<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../controllers/handlers/mod_product_handler.php';
require_once __DIR__ . '/../../config/twig.php';

$fatalError       = '';
$producto         = null;
$categorias       = [];
$estados_producto = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $fatalError = 'ID de producto no válido';
} else {
    $productId    = intval($_GET['id']);
    $userId       = $_SESSION['user_id'];
    $db           = new Database();
    $conn         = $db->getConnection();
    $productModel = new Product($conn);
    $producto     = $productModel->getById($productId);

    if (!$producto) {
        $fatalError = 'Producto no encontrado';
    } elseif ($producto['usuario_id'] != $userId) {
        $fatalError = 'No tienes permiso para editar este producto';
    } else {
        $categorias       = $conn->query('SELECT * FROM Categorias ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
        $estados_producto = $conn->query('SELECT * FROM EstadoProducto ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    }
}

echo $twig->render('mod_product.html.twig', [
    'producto'         => $producto,
    'fatalError'       => $fatalError,
    'categorias'       => $categorias,
    'estados_producto' => $estados_producto,
]);
