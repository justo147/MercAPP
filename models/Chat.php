<?php

/**
 * Modelo Chat
 *
 * Gestiona la creación, obtención y validación de chats
 * entre comprador y vendedor dentro de la plataforma.
 */
class Chat
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
       Obtener chat por ID
    ------------------------------------------ */

    /**
     * Obtiene un chat por su ID.
     *
     * Incluye el título del producto asociado mediante LEFT JOIN.
     *
     * @param int $chatId ID del chat.
     * @return array|null Devuelve los datos del chat o null si no existe.
     */
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

    /**
     * Comprueba si ya existe un chat entre comprador y vendedor
     * para un producto concreto.
     *
     * @param int $productoId ID del producto.
     * @param int $compradorId ID del comprador.
     * @param int $vendedorId ID del vendedor.
     * @return int|false Devuelve el ID del chat si existe, o false si no.
     */
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

    /**
     * Crea un nuevo chat entre comprador y vendedor.
     *
     * @param int $productoId ID del producto.
     * @param int $compradorId ID del comprador.
     * @param int $vendedorId ID del vendedor.
     * @return string ID del chat recién creado.
     */
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

    /**
     * Obtiene un chat existente o crea uno nuevo si no existe.
     *
     * Este método es clave para evitar duplicados:
     * siempre devuelve un único chat por producto y usuarios.
     *
     * @param int $productoId ID del producto.
     * @param int $compradorId ID del comprador.
     * @param int $vendedorId ID del vendedor.
     * @return int|string ID del chat existente o recién creado.
     */
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

    /**
     * Verifica si un usuario forma parte del chat.
     *
     * Se considera miembro si es comprador o vendedor.
     *
     * @param int $chatId ID del chat.
     * @param int $userId ID del usuario.
     * @return bool True si pertenece al chat, false si no.
     */
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
