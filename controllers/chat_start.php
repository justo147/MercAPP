<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../views/auth/login.php");
    exit;
}

if (!isset($_GET["producto_id"]) || !is_numeric($_GET["producto_id"])) {
    die("Producto no válido");
}

$productoId = intval($_GET["producto_id"]);
$usuarioActual = intval($_SESSION["user_id"]);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Chat.php';

$db = new Database();
$conn = $db->getConnection();

$productModel = new Product($conn);
$chatModel = new Chat($conn);

// Obtener producto
$producto = $productModel->getById($productoId);

if (!$producto) {
    die("El producto no existe");
}

$vendedorId = intval($producto["usuario_id"]);

if ($vendedorId === $usuarioActual) {
    header("Location: ../public/views/profile.php?id=" . $usuarioActual);
    exit;
}

// Crear o reutilizar chat
$chatId = $chatModel->getOrCreate($productoId, $usuarioActual, $vendedorId);

// Redirigir al chat
header("Location: ../public/views/chat.php?id=" . $chatId);
exit;
