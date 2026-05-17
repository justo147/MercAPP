<?php
session_start();
require_once __DIR__ . '/../../../config/twig.php';

echo $twig->render('auth/pending_verification.html.twig', [
    'pending_email' => $_SESSION['pending_email'] ?? null,
]);
