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

// ===============================
// LISTADO DE PRODUCTOS
// ===============================
// Array asociativo con productos: nombre, precio e imagen
$productos = [
    ["nombre" => "Teléfono móvil", "precio" => "250€", "imagen" => "📱"],
    ["nombre" => "Portátil", "precio" => "750€", "imagen" => "💻"],
    ["nombre" => "Auriculares", "precio" => "50€", "imagen" => "🎧"],
    ["nombre" => "Cámara", "precio" => "300€", "imagen" => "📷"],
    ["nombre" => "Reloj inteligente", "precio" => "120€", "imagen" => "⌚"],
];
