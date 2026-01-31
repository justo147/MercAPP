<?php
session_start();
$userId = $_SESSION["user_id"];

if (!isset($userId)) {
    header("Location: ../../public/views/auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("ID inválido");
}

$productId = intval($_GET["id"]);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

$db = new Database();
$conn = $db->getConnection();
$productModel = new Product($conn);

// Verificar que el producto pertenece al usuario
$stmt = $conn->prepare("SELECT usuario_id FROM Productos WHERE id = ?");
$stmt->execute([$productId]);
$owner = $stmt->fetchColumn();

if ($owner != $_SESSION["user_id"]) {
    die("No tienes permiso para eliminar este producto");
}

// Eliminar con imágenes
$productModel->deleteWithImages($productId);

// Redirigir al perfil
header("Location: /MercApp/public/views/profile.php?id=$userId&deleted=1");
exit;
