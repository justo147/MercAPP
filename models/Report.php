<?php
require_once __DIR__ . '/../config/db.php';

class Report
{
    /**
     * Conexión a la base de datos.
     * @var PDO
     */
    private $conn;

    /**
     * Constructor del modelo.
     *
     * @param PDO $conn Conexión PDO inyectada.
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /* ============================================================
       CREAR REPORTE
    ============================================================ */

    /**
     * Crea un nuevo reporte.
     *
     * @param int $usuarioId  ID del usuario que reporta.
     * @param int $productoId ID del producto reportado.
     * @param string $motivo  Motivo del reporte.
     * @return bool
     */
    public function create($usuarioId, $productoId, $motivo)
    {
        $sql = "INSERT INTO reportes (usuario_reportador, producto_id, motivo, fecha, estado)
                VALUES (:usuario_reportador, :producto_id, :motivo, NOW(), 'pendiente')";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':usuario_reportador' => $usuarioId,
            ':producto_id'        => $productoId,
            ':motivo'             => $motivo
        ]);
    }

    /* ============================================================
       OBTENER REPORTES
    ============================================================ */

    /**
     * Obtiene todos los reportes.
     *
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT * FROM reportes ORDER BY fecha DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los reportes de un producto.
     *
     * @param int $productoId
     * @return array
     */
    public function getByProduct($productoId)
    {
        $sql = "SELECT * FROM reportes WHERE producto_id = :producto_id ORDER BY fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':producto_id' => $productoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los reportes hechos por un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function getByUser($usuarioId)
    {
        $sql = "SELECT * FROM reportes WHERE usuario_reportador = :usuario_id ORDER BY fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       ACTUALIZAR ESTADO
    ============================================================ */

    /**
     * Cambia el estado de un reporte.
     *
     * @param int $id
     * @param string $estado (pendiente, revisado, rechazado)
     * @param int|null $adminId
     * @return bool
     */
    public function updateStatus($id, $estado, $adminId = null)
    {
        $sql = "UPDATE reportes
                SET estado = :estado, admin_id = :admin_id
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':estado'   => $estado,
            ':admin_id' => $adminId,
            ':id'       => $id
        ]);
    }

    /* ============================================================
       ELIMINAR REPORTE
    ============================================================ */

    /**
     * Elimina un reporte por ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM reportes WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
