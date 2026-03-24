<?php
require_once __DIR__ . '/../config/db.php';

class Transaction
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
       CREAR TRANSACCIÓN
    ============================================================ */

    /**
     * Crea una nueva transacción.
     *
     * @param int $productoId
     * @param int $compradorId
     * @param int $vendedorId
     * @param string $tipo  Tipo de transacción (compra, venta, intercambio)
     * @param string $estado Estado inicial (pendiente, completada, cancelada)
     * @param float $precioFinal Precio final acordado
     * @param float $dineroExtra Dinero añadido en caso de intercambio
     * @return bool
     */
    public function create($productoId, $compradorId, $vendedorId, $tipo, $estado, $precioFinal, $dineroExtra)
    {
        $sql = "INSERT INTO Transacciones 
                (producto_id, comprador_id, vendedor_id, tipo, estado, fecha_transaccion, precio_final, dinero_extra)
                VALUES 
                (:producto_id, :comprador_id, :vendedor_id, :tipo, :estado, NOW(), :precio_final, :dinero_extra)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':producto_id'  => $productoId,
            ':comprador_id' => $compradorId,
            ':vendedor_id'  => $vendedorId,
            ':tipo'         => $tipo,
            ':estado'       => $estado,
            ':precio_final' => $precioFinal,
            ':dinero_extra' => $dineroExtra
        ]);
    }

    /* ============================================================
       OBTENER TRANSACCIONES
    ============================================================ */

    /**
     * Obtiene todas las transacciones.
     *
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT * FROM Transacciones ORDER BY fecha_transaccion DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las transacciones de un usuario (como comprador o vendedor).
     *
     * @param int $usuarioId
     * @return array
     */
    public function getByUser($usuarioId)
    {
        $sql = "SELECT * FROM Transacciones
                WHERE comprador_id = :id OR vendedor_id = :id
                ORDER BY fecha_transaccion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las transacciones de un producto.
     *
     * @param int $productoId
     * @return array
     */
    public function getByProduct($productoId)
    {
        $sql = "SELECT * FROM Transacciones
                WHERE producto_id = :producto_id
                ORDER BY fecha_transaccion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':producto_id' => $productoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       ACTUALIZAR ESTADO
    ============================================================ */

    /**
     * Actualiza el estado de una transacción.
     *
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function updateStatus($id, $estado)
    {
        $sql = "UPDATE Transacciones SET estado = :estado WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':estado' => $estado,
            ':id'     => $id
        ]);
    }

    /* ============================================================
       ELIMINAR TRANSACCIÓN
    ============================================================ */

    /**
     * Elimina una transacción por ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM Transacciones WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }


    public function getActiveTransactionByProduct($productoId)
{
    $sql = "SELECT * FROM Transacciones
            WHERE producto_id = :producto_id
            AND estado IN ('pendiente', 'aceptada')
            ORDER BY id DESC
            LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':producto_id' => $productoId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function createFromChat($productoId, $compradorId, $vendedorId)
{
    $sql = "INSERT INTO Transacciones 
            (producto_id, comprador_id, vendedor_id, tipo, estado, fecha_transaccion)
            VALUES 
            (:producto_id, :comprador_id, :vendedor_id, 'venta', 'pendiente', NOW())";

    $stmt = $this->conn->prepare($sql);

    $stmt->execute([
        ':producto_id'  => $productoId,
        ':comprador_id' => $compradorId,
        ':vendedor_id'  => $vendedorId
    ]);

    return $this->conn->lastInsertId();
}

public function getById($id)
{
    $sql = "SELECT * FROM Transacciones WHERE id = :id LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function getLastTransactionByProduct($productoId)
{
    $sql = "SELECT * FROM Transacciones
            WHERE producto_id = :producto_id
            ORDER BY id DESC
            LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':producto_id' => $productoId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}




}
