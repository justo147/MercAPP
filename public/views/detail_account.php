<?php
require_once __DIR__ . '/../../controllers/check_auth.php';
require_once __DIR__ . '/../../controllers/handlers/detail_account_handlers.php';
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('detail_account.html.twig', [
    'user'    => $user ?? [],
    'error'   => $error ?? '',
    'updated' => isset($_GET['updated']),
]);
