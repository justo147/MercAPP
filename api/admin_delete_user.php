<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado."]);
    exit;
}

$data      = json_decode(file_get_contents("php://input"), true);
$target_id = isset($data['id']) ? intval($data['id']) : 0;

if ($target_id <= 0) {
    echo json_encode(["success" => false, "error" => "ID inválido."]);
    exit;
}

// No puede eliminarse a sí mismo
if ($target_id === intval($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No puedes eliminarte a ti mismo."]);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $userModel = new User($conn);
    $target    = $userModel->getById($target_id);

    if (!$target) {
        echo json_encode(["success" => false, "error" => "Usuario no encontrado."]);
        exit;
    }
    if ($target['rol'] === 'admin') {
        echo json_encode(["success" => false, "error" => "No puedes eliminar a un administrador."]);
        exit;
    }

    $conn->beginTransaction();

    // 1. Valoraciones donde el usuario es valorador o valorado
    $conn->prepare("DELETE FROM Valoraciones WHERE usuario_valorador = ? OR usuario_valorado = ?")->execute([$target_id, $target_id]);

    // 2. Transacciones donde el usuario es comprador o vendedor,
    //    y transacciones sobre sus productos (evita RESTRICT al borrar Productos)
    $prodIds = $conn->prepare("SELECT id FROM Productos WHERE usuario_id = ?");
    $prodIds->execute([$target_id]);
    $ids = $prodIds->fetchAll(PDO::FETCH_COLUMN);

    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        // Valoraciones de esas transacciones
        $conn->prepare("DELETE FROM Valoraciones WHERE transaccion_id IN (SELECT id FROM Transacciones WHERE producto_id IN ($ph))")->execute($ids);
        // Transacciones de terceros sobre los productos del usuario
        $conn->prepare("DELETE FROM Transacciones WHERE producto_id IN ($ph)")->execute($ids);
    }

    // Transacciones donde el usuario es comprador/vendedor (productos de otros)
    $conn->prepare("DELETE FROM Transacciones WHERE comprador_id = ? OR vendedor_id = ?")->execute([$target_id, $target_id]);

    // 3. Borrar imágenes físicas de los productos del usuario
    if ($ids) {
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT url FROM Imagenes_prod WHERE id_producto IN ($ph)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            $ruta = dirname(__DIR__) . '/' . $url;
            if (file_exists($ruta)) @unlink($ruta);
        }
    }

    // 4. Borrar foto de perfil física
    if (!empty($target['foto_perfil'])) {
        $ruta = dirname(__DIR__) . '/' . $target['foto_perfil'];
        if (file_exists($ruta)) @unlink($ruta);
    }

    // 5. Eliminar el usuario (el resto cascada: Productos, Imagenes_prod,
    //    Notificaciones, Chat, Mensajes, Deseos, Favoritos, Seguidores, Reportes)
    $conn->prepare("DELETE FROM usuario WHERE id = ?")->execute([$target_id]);

    $conn->commit();

    echo json_encode(["success" => true, "message" => "Usuario eliminado permanentemente de la base de datos."]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log("admin_delete_user error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno: " . $e->getMessage()]);
}
