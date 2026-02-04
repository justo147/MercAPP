<?php
require_once __DIR__ . '/../config/db.php';

class Notification
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
       CREAR NOTIFICACIÓN
    ============================================================ */

    /**
     * Crea una nueva notificación para un usuario.
     *
     * @param int $usuarioId
     * @param string $tipo
     * @param string $contenido
     * @return bool
     */
    public function create($usuarioId, $tipo, $contenido)
    {
        $sql = "INSERT INTO notificaciones (usuario_id, tipo, contenido, fecha, leida)
                VALUES (:usuario_id, :tipo, :contenido, NOW(), 0)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo'       => $tipo,
            ':contenido'  => $contenido
        ]);
    }

    /* ============================================================
       OBTENER NOTIFICACIONES
    ============================================================ */

    /**
     * Obtiene todas las notificaciones de un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function getByUser($usuarioId)
    {
        $sql = "SELECT * FROM notificaciones
                WHERE usuario_id = :usuario_id
                ORDER BY fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo las notificaciones no leídas de un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function getUnread($usuarioId)
    {
        $sql = "SELECT * FROM notificaciones
                WHERE usuario_id = :usuario_id AND leida = 0
                ORDER BY fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       MARCAR COMO LEÍDA
    ============================================================ */

    /**
     * Marca una notificación como leída.
     *
     * @param int $id
     * @return bool
     */
    public function markAsRead($id)
    {
        $sql = "UPDATE notificaciones SET leida = 1 WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas.
     *
     * @param int $usuarioId
     * @return bool
     */
    public function markAllAsRead($usuarioId)
    {
        $sql = "UPDATE notificaciones SET leida = 1 WHERE usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':usuario_id' => $usuarioId]);
    }

    /* ============================================================
       ELIMINAR
    ============================================================ */

    /**
     * Elimina una notificación por ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM notificaciones WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
