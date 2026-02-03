<?php

class Chat
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /* -----------------------------------------
       Obtener chat por ID
    ------------------------------------------ */
    public function getById(int $chatId)
    {
        $sql = "SELECT c.*, p.titulo AS producto_titulo
                FROM Chat c
                LEFT JOIN Productos p ON c.producto_id = p.id
                WHERE c.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $chatId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* -----------------------------------------
       Obtener chat existente entre comprador y vendedor
       para un producto concreto
    ------------------------------------------ */
    public function getExistingChat(int $productoId, int $compradorId, int $vendedorId)
    {
        $sql = "SELECT id FROM Chat
                WHERE producto_id = :pid
                AND usuario_comprador = :comprador
                AND usuario_vendedor = :vendedor
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":pid" => $productoId,
            ":comprador" => $compradorId,
            ":vendedor" => $vendedorId
        ]);

        return $stmt->fetchColumn();
    }

    /* -----------------------------------------
       Crear chat nuevo
    ------------------------------------------ */
    public function create(int $productoId, int $compradorId, int $vendedorId)
    {
        $sql = "INSERT INTO Chat (producto_id, usuario_comprador, usuario_vendedor)
                VALUES (:pid, :comprador, :vendedor)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":pid" => $productoId,
            ":comprador" => $compradorId,
            ":vendedor" => $vendedorId
        ]);

        return $this->conn->lastInsertId();
    }

    /* -----------------------------------------
       Obtener o crear chat (método clave)
    ------------------------------------------ */
    public function getOrCreate(int $productoId, int $compradorId, int $vendedorId)
    {
        $chatId = $this->getExistingChat($productoId, $compradorId, $vendedorId);

        if ($chatId) {
            return $chatId;
        }

        return $this->create($productoId, $compradorId, $vendedorId);
    }

    /* -----------------------------------------
       Verificar si un usuario pertenece al chat
    ------------------------------------------ */
    public function userBelongsToChat(int $chatId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM Chat
                WHERE id = :id
                AND (usuario_comprador = :uid OR usuario_vendedor = :uid)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":id" => $chatId,
            ":uid" => $userId
        ]);

        return $stmt->fetchColumn() > 0;
    }
}
