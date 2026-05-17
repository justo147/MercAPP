<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';
require_once __DIR__ . '/../../config/twig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

echo $twig->render('home.html.twig', [
    'app_query' => htmlspecialchars($_GET['q'] ?? ''),
]);
