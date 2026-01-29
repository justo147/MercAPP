<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Categorías
    $stmt = $conn->query("SELECT id, nombre FROM Categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estado del producto
    $stmt = $conn->query("SELECT id, nombre FROM EstadoProducto ORDER BY id ASC");
    $estadoProducto = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tipos de transacción (ENUM)
    $stmt = $conn->query("SHOW COLUMNS FROM Productos LIKE 'tipo_transaccion'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    preg_match("/^enum\((.*)\)$/", $row['Type'], $matches);
    $enumValues = array_map(fn($v) => trim($v, "'"), explode(",", $matches[1]));

    // Estado de publicación
    $stmt = $conn->query("SELECT id, nombre FROM EstadoPublicacion ORDER BY id ASC");
    $estadoPublicacion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "categorias" => $categorias,
        "estado_producto" => $estadoProducto,
        "tipos_transaccion" => $enumValues,
        "estado_publicacion" => $estadoPublicacion
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
