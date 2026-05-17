<?php
require_once __DIR__ . '/../../../controllers/handlers/login_handlers.php';
require_once __DIR__ . '/../../../config/twig.php';

echo $twig->render('auth/login.html.twig', [
    'error_message' => $error_message ?? null,
    'post_email'    => $_POST['email'] ?? '',
]);
