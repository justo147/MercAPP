<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

session_start();

$db   = new Database();
$conn = $db->getConnection();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) { header("Location: home.php"); exit; }

$productModel = new Product($conn);
$producto     = $productModel->getById($productId);
if (!$producto)  { header("Location: home.php"); exit; }

$vendedorId      = $producto['usuario_id'] ?? null;
$vendedor        = null;
$yaSigue         = false;
$usuarioLogueado = $_SESSION['user_id'] ?? null;

if ($vendedorId) {
    require_once __DIR__ . '/../../models/User.php';
    $userModel = new User($conn);
    $vendedor  = $userModel->getById($vendedorId);
    if ($usuarioLogueado && $usuarioLogueado != $vendedorId) {
        $yaSigue = $userModel->sigueA($usuarioLogueado, $vendedorId);
    }
}

$productosSugeridos = $productModel->getRandomProducts(20, $productId);
$imagenes    = is_array($producto['imagenes']) ? $producto['imagenes'] : [];
$imgPrincipal = !empty($imagenes)
    ? $BASE . '/' . $imagenes[0]['url']
    : $BASE . '/public/img/default.jpg';

$precioStr = (!empty($producto['precio']) && (float)$producto['precio'] > 0)
    ? number_format((float)$producto['precio'], 2) . ' €'
    : 'Gratis';

$tipoBadge = [
    'venta'       => ['bg-primary',           'Venta'],
    'intercambio' => ['bg-warning text-dark',  'Intercambio'],
    'mixto'       => ['bg-success',            'Venta / Intercambio'],
];
[$tipoCls, $tipoLabel] = $tipoBadge[$producto['tipo_transaccion'] ?? '']
    ?? ['bg-secondary', ucfirst($producto['tipo_transaccion'] ?? '')];

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('detail_product.html.twig', [
    'producto'           => $producto,
    'imagenes'           => $imagenes,
    'imgPrincipal'       => $imgPrincipal,
    'precioStr'          => $precioStr,
    'tipoCls'            => $tipoCls,
    'tipoLabel'          => $tipoLabel,
    'vendedor'           => $vendedor,
    'vendedorId'         => $vendedorId,
    'yaSigue'            => $yaSigue,
    'usuarioLogueado'    => $usuarioLogueado,
    'productosSugeridos' => $productosSugeridos,
]);
