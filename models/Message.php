<?php

/**
 * Modelo Message
 *
 * Gestiona los mensajes enviados dentro de un chat.
 * Permite obtener todos los mensajes de un chat y enviar nuevos mensajes.
 */
class Message
{
    /**
     * Conexión a la base de datos.
     *
     * @var PDO
     */
    private $conn;

    /**
     * Constructor del modelo.
     *
     * @param PDO $conn Conexión PDO inyectada.
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /* -----------------------------------------
       Get all messages from a chat
    ------------------------------------------ */

    /**
     * Obtiene todos los mensajes pertenecientes a un chat.
     *
     * Incluye el nombre del usuario que envía cada mensaje.
     * Los mensajes se devuelven ordenados por fecha de envío.
     *
     * @param int $chatId ID del chat.
     * @return array Lista de mensajes con información del remitente.
     */
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

    /**
     * Envía un mensaje dentro de un chat.
     *
     * Inserta un nuevo registro en la tabla Mensajes.
     *
     * @param int $chatId ID del chat.
     * @param int $userId ID del usuario que envía el mensaje.
     * @param string $content Contenido del mensaje.
     * @return bool True si el mensaje se envió correctamente, false si falló.
     */
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
