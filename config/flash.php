<?php
/**
 * flash.php — helpers para mensajes flash via sesión
 *
 * Uso:
 *   setFlash('success', 'Producto publicado correctamente.');
 *   header('Location: ...');  exit;
 *
 * Los toasts se emiten automáticamente desde navbar.php en la siguiente carga.
 */

function setFlash(string $tipo, string $mensaje): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function hasFlash(): bool {
    return !empty($_SESSION['_flash']);
}
