<?php
require_once __DIR__ . '/../config/db.php';

class Transaction
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /* ============================================================
       CREAR
    ============================================================ */

    /**
     * Crea una transacción básica (usada por createFromChat).
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
            ':dinero_extra' => $dineroExtra,
        ]);
    }

    /**
     * Crea una transacción desde el chat (el vendedor la inicia).
     * Estado inicial: 'pendiente'.
     */
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
            ':vendedor_id'  => $vendedorId,
        ]);

        return $this->conn->lastInsertId();
    }

    /* ============================================================
       OBTENER
    ============================================================ */

    public function getAll()
    {
        $sql = "SELECT * FROM Transacciones ORDER BY fecha_transaccion DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql  = "SELECT * FROM Transacciones WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUser($usuarioId)
    {
        $sql  = "SELECT * FROM Transacciones
                 WHERE comprador_id = :id OR vendedor_id = :id
                 ORDER BY fecha_transaccion DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByProduct($productoId)
    {
        $sql  = "SELECT * FROM Transacciones
                 WHERE producto_id = :producto_id
                 ORDER BY fecha_transaccion DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':producto_id' => $productoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveTransactionByProduct($productoId)
    {
        $sql  = "SELECT * FROM Transacciones
                 WHERE producto_id = :producto_id
                   AND estado IN ('pendiente','aceptada','pago_pendiente','enviado')
                 ORDER BY id DESC
                 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':producto_id' => $productoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLastTransactionByProduct($productoId)
    {
        $sql  = "SELECT * FROM Transacciones
                 WHERE producto_id = :producto_id
                 ORDER BY id DESC
                 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':producto_id' => $productoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       ACTUALIZAR — estado general
    ============================================================ */

    public function updateStatus($id, $estado)
    {
        $sql  = "UPDATE Transacciones SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /* ============================================================
       ACTUALIZAR — transiciones del flujo realista
    ============================================================ */

    /**
     * Comprador acepta: guarda método de pago, dirección de envío y notas.
     * Registra timestamp de aceptación.
     */
    public function aceptar($id, $metodoPago, $direccionEnvio, $notasComprador)
    {
        $sql = "UPDATE Transacciones
                SET estado            = 'aceptada',
                    metodo_pago       = :metodo_pago,
                    direccion_envio   = :direccion_envio,
                    notas_comprador   = :notas,
                    fecha_aceptacion  = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':metodo_pago'     => $metodoPago,
            ':direccion_envio' => $direccionEnvio,
            ':notas'           => $notasComprador ?: null,
            ':id'              => $id,
        ]);
    }

    /**
     * Comprador informa que ha pagado → pago_pendiente.
     */
    public function marcarPagado($id)
    {
        $sql  = "UPDATE Transacciones
                 SET estado = 'pago_pendiente', fecha_pago_confirmado = NOW()
                 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Vendedor confirma pago y marca como enviado con número de seguimiento.
     */
    public function marcarEnviado($id, $numeroSeguimiento)
    {
        $sql = "UPDATE Transacciones
                SET estado              = 'enviado',
                    numero_seguimiento  = :seguimiento,
                    fecha_envio         = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':seguimiento' => $numeroSeguimiento ?: null,
            ':id'          => $id,
        ]);
    }

    /**
     * Comprador confirma entrega.
     */
    public function marcarEntregado($id)
    {
        $sql  = "UPDATE Transacciones
                 SET estado = 'entregado', fecha_entrega = NOW()
                 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Cancelar transacción.
     */
    public function cancelar($id)
    {
        $sql  = "UPDATE Transacciones SET estado = 'cancelada' WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /* ============================================================
       ELIMINAR
    ============================================================ */

    public function delete($id)
    {
        $sql  = "DELETE FROM Transacciones WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
