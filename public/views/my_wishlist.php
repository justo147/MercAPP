<?php
session_start();
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/check_auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
$db   = new Database();
$conn = $db->getConnection();

$categorias      = $conn->query('SELECT id, nombre FROM Categorias ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$estadosProducto = $conn->query('SELECT id, nombre FROM EstadoProducto ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('my_wishlist.html.twig', [
    'categorias'      => $categorias,
    'estadosProducto' => $estadosProducto,
]);
