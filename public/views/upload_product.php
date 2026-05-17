<?php
require_once __DIR__ . '/../../controllers/handlers/upload_product_handler.php';
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('upload_product.html.twig', [
    'categorias'      => $categorias      ?? [],
    'estados_producto' => $estados_producto ?? [],
    'post_ubicacion'  => $_POST['ubicacion'] ?? '',
    'post_lat'        => $_POST['lat'] ?? '',
    'post_lon'        => $_POST['lon'] ?? '',
]);
