<?php
/**
 * report_handler.php
 *
 * Procesa el envío de un reporte sobre un producto.
 * Puede usarse desde cualquier vista que tenga acceso al producto_id.
 *
 * Campos POST esperados:
 *   - producto_id  (int)
 *   - motivo       (string)
 *   - redirect_to  (string, opcional) — URL a la que volver tras el reporte
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

$usuarioId  = intval($_SESSION["user_id"]);
$productoId = intval($_POST["producto_id"] ?? 0);
$motivo     = trim($_POST["motivo"] ?? "");
$redirectTo = trim($_POST["redirect_to"] ?? "");

// Validar destino de redirección (solo rutas internas)
if (empty($redirectTo) || !str_starts_with($redirectTo, '/')) {
    $redirectTo = "{$BASE}/public/views/home.php";
}

if ($productoId <= 0 || $motivo === "") {
    header("Location: {$redirectTo}?reporte=error");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Report.php';

$db          = new Database();
$conn        = $db->getConnection();
$reportModel = new Report($conn);

$ok = $reportModel->create($usuarioId, $productoId, $motivo);

header("Location: {$redirectTo}" . ($ok ? "?reporte=ok" : "?reporte=error"));
exit;
