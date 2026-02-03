<?php

class Message
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /* -----------------------------------------
       Get all messages from a chat
    ------------------------------------------ */
    public function getByChat(int $chatId)
    {
        $sql = "SELECT m.*, u.nombre AS sender_name
                FROM Mensajes m
                JOIN Usuario u ON m.usuario_id = u.id
                WHERE m.chat_id = :chat
                ORDER BY m.fecha_envio ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":chat" => $chatId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* -----------------------------------------
       Send a message
    ------------------------------------------ */
    public function send(int $chatId, int $userId, string $content)
    {
        $sql = "INSERT INTO Mensajes (chat_id, usuario_id, contenido)
                VALUES (:chat, :uid, :content)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":chat" => $chatId,
            ":uid" => $userId,
            ":content" => $content
        ]);
    }
}
