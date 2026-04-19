<?php
/**
 * Guarda la preferencia de tema (dark/light) en la sesión del usuario.
 * POST { theme: "dark"|"light" }
 */
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$theme = trim($_POST['theme'] ?? '');

if (!in_array($theme, ['dark', 'light'])) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$_SESSION['theme'] = $theme;
echo json_encode(['ok' => true]);
