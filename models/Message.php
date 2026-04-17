<?php

/**
 * Modelo Message
 *
 * Gestiona los mensajes enviados dentro de un chat.
 */
class Message
{
    /** @var PDO */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /* -----------------------------------------
       Obtener todos los mensajes de un chat
    ------------------------------------------ */

    /**
     * Obtiene todos los mensajes de un chat, incluyendo los mensajes del sistema
     * (usuario_id = NULL). Usa LEFT JOIN para no excluirlos.
     *
     * @param int $chatId
     * @return array
     */
    public function getByChat(int $chatId): array
    {
        $sql = "SELECT m.*, u.nombre AS sender_name
                FROM Mensajes m
                LEFT JOIN usuario u ON m.usuario_id = u.id
                WHERE m.chat_id = :chat
                ORDER BY m.fecha_envio ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":chat" => $chatId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* -----------------------------------------
       Enviar mensaje
    ------------------------------------------ */

    /**
     * @param int    $chatId
     * @param int    $userId
     * @param string $content
     * @return bool
     */
    public function send(int $chatId, int $userId, string $content): bool
    {
        $sql = "INSERT INTO Mensajes (chat_id, usuario_id, contenido)
                VALUES (:chat, :uid, :content)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":chat"    => $chatId,
            ":uid"     => $userId,
            ":content" => $content
        ]);
    }

    /* -----------------------------------------
       Marcar como leídos
    ------------------------------------------ */

    /**
     * Marca como leídos los mensajes del otro usuario (no los propios ni los del sistema).
     *
     * @param int $chatId
     * @param int $userId ID del usuario que está leyendo
     */
    public function markAsRead(int $chatId, int $userId): void
    {
        $sql = "UPDATE Mensajes
                SET leido = 1
                WHERE chat_id  = :chat
                AND usuario_id IS NOT NULL
                AND usuario_id != :uid
                AND leido = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":chat" => $chatId, ":uid" => $userId]);
    }

    /**
     * Marca como leídos los mensajes del sistema de un chat.
     *
     * @param int $chatId
     */
    public function markSystemAsRead(int $chatId): void
    {
        $sql = "UPDATE Mensajes
                SET leido = 1
                WHERE chat_id     = :chat
                AND usuario_id IS NULL
                AND leido = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":chat" => $chatId]);
    }

    /* -----------------------------------------
       Contadores de mensajes sin leer
    ------------------------------------------ */

    /**
     * Mensajes sin leer del otro usuario en un chat concreto.
     * Excluye mensajes del sistema (usuario_id IS NULL).
     *
     * @param int $chatId
     * @param int $userId
     * @return int
     */
    public function countUnread(int $chatId, int $userId): int
    {
        $sql = "SELECT COUNT(*)
                FROM Mensajes
                WHERE chat_id     = :chat
                AND usuario_id IS NOT NULL
                AND usuario_id   != :uid
                AND leido = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":chat" => $chatId, ":uid" => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Total de mensajes sin leer del usuario en todos sus chats.
     * Excluye mensajes del sistema.
     *
     * @param int $userId
     * @return int
     */
    public function countAllUnread(int $userId): int
    {
        $sql = "SELECT COUNT(*)
                FROM Mensajes m
                JOIN Chat c ON m.chat_id = c.id
                WHERE m.usuario_id IS NOT NULL
                AND m.usuario_id  != :uid
                AND m.leido = 0
                AND (c.usuario_comprador = :uid OR c.usuario_vendedor = :uid)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":uid" => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /* -----------------------------------------
       Mensajes del sistema
    ------------------------------------------ */

    /**
     * Inserta un mensaje automático del sistema (usuario_id = NULL).
     *
     * @param int    $chatId
     * @param string $texto  Sin prefijo — se añade aquí
     * @return bool
     */
    public function enviarMensajeSistema(int $chatId, string $texto): bool
    {
        $sql = "INSERT INTO Mensajes (chat_id, usuario_id, contenido, leido)
                VALUES (:chat, NULL, :contenido, 0)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':chat'      => $chatId,
            ':contenido' => '[SISTEMA] ' . $texto
        ]);
    }
}
