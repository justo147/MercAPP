<?php
require_once __DIR__ . '/../../../controllers/handlers/register_handlers.php';
require_once __DIR__ . '/../../../config/twig.php';

echo $twig->render('auth/register.html.twig', []);
