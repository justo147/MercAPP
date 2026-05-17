<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php');
    exit;
}

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('admin_dashboard.html.twig', []);
