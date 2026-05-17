<?php
require_once __DIR__ . '/../../controllers/handlers/verify_email_handlers.php';
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('verify_email.html.twig', [
    'estado' => $estado ?? 'Procesando verificación…',
]);
