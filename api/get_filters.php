<?php
/**
 * API Endpoint: Obtener Filtros Dinámicos.
 *
 * Este script consulta la base de datos para obtener las categorías, estados de producto,
 * tipos de transacción (desde un ENUM) y estados de publicación. Se utiliza para
 * inicializar selectores y filtros en el frontend.
 *
 * @package MercApp\API
 * @author Tu Nombre
 * @version 1.0
 */

header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';

/**
 * @section Respuesta JSON
 * @example
 * {
 * "success": true,
 * "categorias": [{"id": 1, "nombre": "Electrónica"}],
 * "estado_producto": [{"id": 1, "nombre": "Nuevo"}],
 * "tipos_transaccion": ["Venta", "Intercambio"],
 * "estado_publicacion": [{"id": 1, "nombre": "Activo"}]
 * }
 */

try {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Obtener Categorías
    $stmt = $conn->query("SELECT id, nombre FROM Categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener Estados físicos del producto
    $stmt = $conn->query("SELECT id, nombre FROM EstadoProducto ORDER BY id ASC");
    $estadoProducto = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener Tipos de transacción (Desde la definición del campo ENUM)
    $stmt = $conn->query("SHOW COLUMNS FROM Productos LIKE 'tipo_transaccion'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    preg_match("/^enum\((.*)\)$/", $row['Type'], $matches);
    $enumValues = array_map(fn($v) => trim($v, "'"), explode(",", $matches[1]));

    // 4. Obtener Estados de publicación
    $stmt = $conn->query("SELECT id, nombre FROM EstadoPublicacion ORDER BY id ASC");
    $estadoPublicacion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * @return string Retorna un objeto JSON con los arrays de configuración.
     */
    echo json_encode([
        "success" => true,
        "categorias" => $categorias,
        "estado_producto" => $estadoProducto,
        "tipos_transaccion" => $enumValues,
        "estado_publicacion" => $estadoPublicacion
    ]);

} catch (Exception $e) {
    /**
     * @return string Retorna un objeto JSON con success: false y el mensaje de error.
     */
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}