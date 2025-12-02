<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
}

$productos = [
    ["nombre" => "Teléfono móvil", "precio" => "250€", "imagen" => "📱"],
    ["nombre" => "Portátil", "precio" => "750€", "imagen" => "💻"],
    ["nombre" => "Auriculares", "precio" => "50€", "imagen" => "🎧"],
    ["nombre" => "Cámara", "precio" => "300€", "imagen" => "📷"],
    ["nombre" => "Reloj inteligente", "precio" => "120€", "imagen" => "⌚"],
];