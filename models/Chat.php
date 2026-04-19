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
        $sql = "SELECT c.*,
                   p.titulo AS producto_titulo,
                   img.url AS producto_imagen,
                   uc.nombre       AS comprador_nombre,
                   uc.foto_perfil  AS comprador_foto,
                   uv.nombre       AS vendedor_nombre,
                   uv.foto_perfil  AS vendedor_foto
            FROM Chat c
            LEFT JOIN Productos p   ON c.producto_id = p.id
            LEFT JOIN Imagenes_prod img ON img.id_producto = p.id AND img.orden = 0
            LEFT JOIN Usuario uc    ON uc.id = c.usuario_comprador
            LEFT JOIN Usuario uv    ON uv.id = c.usuario_vendedor
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
     * @return array{id:int, nuevo:bool} ID del chat existente o recién creado.
     */
   public function getOrCreate(int $productoId, int $compradorId, int $vendedorId)
{
    $chatId = $this->getExistingChat($productoId, $compradorId, $vendedorId);

    if ($chatId) {
        return [
            "id" => $chatId,
            "nuevo" => false
        ];
    }

    $nuevoId = $this->create($productoId, $compradorId, $vendedorId);

    return [
        "id" => $nuevoId,
        "nuevo" => true
    ];
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


    /**
 * Obtiene todos los chats activos en los que participa un usuario.
 *
 * Incluye:
 * - título del producto
 * - primera imagen del producto
 * - último mensaje
 * - número de mensajes sin leer
 */
/**
 * @param int    $userId  Usuario en sesión
 * @param string $filtro  '' | 'no_leidos' | 'con_transaccion' | 'sin_transaccion'
 *                        | 'abierto' (transacción activa) | 'cerrado' (entregado/cancelada)
 */
public function getChatsByUser(int $userId, string $filtro = '')
{
    $sql = "
        SELECT
            c.id AS chat_id,
            c.producto_id,
            c.usuario_comprador,
            c.usuario_vendedor,
            c.transaccion_id,
            p.titulo AS producto_titulo,
            img.url  AS producto_imagen,

            -- Otro participante
            CASE
                WHEN c.usuario_comprador = :uid THEN uv.nombre
                ELSE uc.nombre
            END AS otro_nombre,
            CASE
                WHEN c.usuario_comprador = :uid THEN uv.foto_perfil
                ELSE uc.foto_perfil
            END AS otro_foto,
            CASE
                WHEN c.usuario_comprador = :uid THEN uv.id
                ELSE uc.id
            END AS otro_id,

            -- Último mensaje
            (SELECT contenido  FROM Mensajes WHERE chat_id = c.id ORDER BY fecha_envio DESC LIMIT 1) AS ultimo_mensaje,
            (SELECT fecha_envio FROM Mensajes WHERE chat_id = c.id ORDER BY fecha_envio DESC LIMIT 1) AS fecha_ultimo_mensaje,

            -- Mensajes sin leer
            (SELECT COUNT(*) FROM Mensajes
             WHERE chat_id = c.id AND usuario_id IS NOT NULL
               AND usuario_id != :uid AND leido = 0) AS no_leidos,

            -- Estado de transacción (NULL si no tiene)
            t.estado AS transaccion_estado

        FROM Chat c
        JOIN Productos p     ON p.id = c.producto_id
        LEFT JOIN Imagenes_prod img ON img.id_producto = p.id AND img.orden = 0
        LEFT JOIN Usuario uc ON uc.id = c.usuario_comprador
        LEFT JOIN Usuario uv ON uv.id = c.usuario_vendedor
        LEFT JOIN Transacciones t ON t.id = c.transaccion_id
        WHERE (c.usuario_comprador = :uid OR c.usuario_vendedor = :uid)
    ";

    // Aplicar filtros
    switch ($filtro) {
        case 'no_leidos':
            $sql .= " AND (SELECT COUNT(*) FROM Mensajes WHERE chat_id = c.id
                          AND usuario_id IS NOT NULL AND usuario_id != :uid AND leido = 0) > 0";
            break;
        case 'con_transaccion':
            $sql .= " AND c.transaccion_id IS NOT NULL";
            break;
        case 'sin_transaccion':
            $sql .= " AND c.transaccion_id IS NULL";
            break;
        case 'abierto':
            $sql .= " AND t.estado IN ('pendiente','aceptada','pago_pendiente','enviado')";
            break;
        case 'cerrado':
            $sql .= " AND t.estado IN ('entregado','cancelada')";
            break;
    }

    $sql .= " ORDER BY fecha_ultimo_mensaje DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([":uid" => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function setTransaction(int $chatId, int $transactionId)
{
    $sql = "UPDATE Chat SET transaccion_id = :tid WHERE id = :chatId";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        ':tid' => $transactionId,
        ':chatId' => $chatId
    ]);
}


}
