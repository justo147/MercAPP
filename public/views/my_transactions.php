<?php
session_start();
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('my_transactions.html.twig', [
    'usuarioId' => intval($_SESSION['user_id']),
]);
