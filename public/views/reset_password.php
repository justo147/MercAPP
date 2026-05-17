<?php
require __DIR__ . '/../../controllers/handlers/reset_password_handlers.php';
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('reset_password.html.twig', [
    'message' => $message ?? null,
    'token'   => $_GET['token'] ?? '',
]);
