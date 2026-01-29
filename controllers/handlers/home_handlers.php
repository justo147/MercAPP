<?php
/**
 * Script de ejemplo para mostrar productos a un usuario logueado.
 * 
 * Comprueba si el usuario tiene sesión activa y define un listado de productos
 * con nombre, precio e imagen (emoji como ejemplo).
 */

session_start();

// ===============================
// CONTROL DE ACCESO
// ===============================
// Si el usuario no ha iniciado sesión, redirigir al login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

