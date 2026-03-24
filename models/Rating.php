<?php
require_once __DIR__ . '/../config/db.php';

class Rating
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
       CREAR VALORACIÓN
    ============================================================ */

    /**
     * Crea una nueva valoración asociada a una transacción.
     *
     * @param int $transaccionId ID de la transacción valorada.
     * @param int $usuarioValorador ID del usuario que realiza la valoración.
     * @param int $usuarioValorado ID del usuario que recibe la valoración.
     * @param int $puntuacion Puntuación general (1–5).
     * @param string|null $comentario Comentario opcional.
     * @param int|null $fiabilidad Valoración de fiabilidad (1–5).
     * @param int|null $comunicacion Valoración de comunicación (1–5).
     * @param int|null $puntualidad Valoración de puntualidad (1–5).
     * @return bool
     */
    public function create($transaccionId, $usuarioValorador, $usuarioValorado, $puntuacion, $comentario = null, $fiabilidad = null, $comunicacion = null, $puntualidad = null)
    {
        $sql = "INSERT INTO Valoraciones 
                (transaccion_id, usuario_valorador, usuario_valorado, puntuacion, comentario, fecha_valoracion, fiabilidad, comunicacion, puntualidad)
                VALUES 
                (:transaccion_id, :usuario_valorador, :usuario_valorado, :puntuacion, :comentario, NOW(), :fiabilidad, :comunicacion, :puntualidad)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':transaccion_id'   => $transaccionId,
            ':usuario_valorador'=> $usuarioValorador,
            ':usuario_valorado' => $usuarioValorado,
            ':puntuacion'       => $puntuacion,
            ':comentario'       => $comentario,
            ':fiabilidad'       => $fiabilidad,
            ':comunicacion'     => $comunicacion,
            ':puntualidad'      => $puntualidad
        ]);
    }

    /* ============================================================
       OBTENER VALORACIONES
    ============================================================ */

    /**
     * Obtiene todas las valoraciones registradas.
     *
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT * FROM Valoraciones ORDER BY fecha_valoracion DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las valoraciones recibidas por un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function getReceivedByUser($usuarioId)
    {
        $sql = "SELECT * FROM Valoraciones 
                WHERE usuario_valorado = :id
                ORDER BY fecha_valoracion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las valoraciones realizadas por un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function getGivenByUser($usuarioId)
    {
        $sql = "SELECT * FROM Valoraciones 
                WHERE usuario_valorador = :id
                ORDER BY fecha_valoracion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la valoración asociada a una transacción específica.
     *
     * @param int $transaccionId
     * @return array|null
     */
    public function getByTransaction($transaccionId)
    {
        $sql = "SELECT * FROM Valoraciones WHERE transaccion_id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $transaccionId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       PROMEDIOS
    ============================================================ */

    /**
     * Obtiene la puntuación media general de un usuario.
     *
     * @param int $usuarioId
     * @return float|null
     */
    public function getAverageScore($usuarioId)
    {
        $sql = "SELECT AVG(puntuacion) AS promedio 
                FROM Valoraciones 
                WHERE usuario_valorado = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['promedio'] ?? null;
    }

    /**
     * Obtiene los promedios detallados de fiabilidad, comunicación y puntualidad.
     *
     * @param int $usuarioId
     * @return array|null
     */
    public function getDetailedAverages($usuarioId)
    {
        $sql = "SELECT 
                    AVG(fiabilidad) AS promedio_fiabilidad,
                    AVG(comunicacion) AS promedio_comunicacion,
                    AVG(puntualidad) AS promedio_puntualidad
                FROM Valoraciones
                WHERE usuario_valorado = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       COMPROBACIONES
    ============================================================ */

    /**
     * Comprueba si un usuario ya ha valorado una transacción concreta.
     *
     * @param int $transaccionId
     * @param int $usuarioValorador
     * @return bool
     */
    public function hasRated($transaccionId, $usuarioValorador)
    {
        $sql = "SELECT COUNT(*) FROM Valoraciones
                WHERE transaccion_id = :transaccion_id
                  AND usuario_valorador = :usuario_valorador";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':transaccion_id'    => $transaccionId,
            ':usuario_valorador' => $usuarioValorador
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /* ============================================================
       ELIMINAR
    ============================================================ */

    /**
     * Elimina una valoración por su ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM Valoraciones WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}