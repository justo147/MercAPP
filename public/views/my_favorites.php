<?php
session_start();
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT p.id, p.titulo, p.precio,
           (SELECT url FROM Imagenes_prod WHERE id_producto = p.id ORDER BY orden ASC LIMIT 1) AS imagen
    FROM Favoritos f
    JOIN Productos p ON p.id = f.producto_id
    WHERE f.usuario_id = :uid
");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('my_favorites.html.twig', [
    'favoritos' => $favoritos,
]);
