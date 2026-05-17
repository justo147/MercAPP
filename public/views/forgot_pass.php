<?php
require __DIR__ . '/../../controllers/handlers/process_forgot_handlers.php';
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('forgot_pass.html.twig', [
    'mensaje' => $mensaje ?? null,
]);
