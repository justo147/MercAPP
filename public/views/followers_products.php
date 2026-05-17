<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/auth/login.php');
    exit;
}

$userId    = (int) $_SESSION['user_id'];
$db        = new Database();
$conn      = $db->getConnection();
$userModel = new User($conn);

$siguiendo    = $userModel->obtenerSeguidos($userId);
$siguiendoIds = array_column($siguiendo, 'id');

$sugerencias = [];
try {
    $excludeSql = '';
    $allParams  = [$userId];
    if (!empty($siguiendoIds)) {
        $ph         = implode(',', array_fill(0, count($siguiendoIds), '?'));
        $excludeSql = "AND u.id NOT IN ($ph)";
        $allParams  = array_merge($allParams, $siguiendoIds);
    }
    $stmtSug = $conn->prepare("
        SELECT u.id, u.nombre, u.apellidos, u.foto_perfil, COUNT(p.id) AS total_productos
        FROM   Usuario u
        JOIN   Productos p          ON p.usuario_id      = u.id
        JOIN   EstadoPublicacion ep ON ep.id             = p.estado_publicacion_id AND ep.nombre = 'activo'
        WHERE  u.id != ? $excludeSql
        GROUP  BY u.id HAVING total_productos > 0
        ORDER  BY total_productos DESC LIMIT 8
    ");
    $stmtSug->execute($allParams);
    $sugerencias = $stmtSug->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* silencioso */ }

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('followers_products.html.twig', [
    'siguiendo'   => $siguiendo,
    'sugerencias' => $sugerencias,
    'usuarioId'   => $userId,
]);
