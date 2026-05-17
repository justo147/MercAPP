<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../config/twig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

$perfilId        = intval($_GET['id'] ?? 0);
$usuarioLogueado = $_SESSION['user_id'];
$esPropietario   = ($perfilId === $usuarioLogueado);

$user   = null;
$yaSigue = false;

if ($perfilId > 0) {
    $db   = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE id = ?");
    $stmt->execute([$perfilId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user && !$esPropietario) {
        $userModel = new User($conn);
        $yaSigue   = $userModel->sigueA($usuarioLogueado, $perfilId);
    }
}

echo $twig->render('profile.html.twig', [
    'user'            => $user,
    'perfilId'        => $perfilId,
    'esPropietario'   => $esPropietario,
    'usuarioLogueado' => $usuarioLogueado,
    'yaSigue'         => $yaSigue,
]);
