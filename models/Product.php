<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Modelo Product
 *
 * Gestiona todas las operaciones relacionadas con productos:
 * - creación, actualización y eliminación
 * - obtención de productos e imágenes
 * - búsquedas avanzadas
 * - paginación
 * - gestión de imágenes asociadas
 */
class Product
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
       Obtener productos paginados (Home)
    ------------------------------------------ */

    /**
     * Obtiene productos paginados para la página principal.
     *
     * Incluye:
     * - nombre del usuario
     * - nombre de la categoría
     * - imágenes asociadas
     *
     * @param int $limit Número de productos a mostrar.
     * @param int $offset Desplazamiento para paginación.
     * @return array Lista de productos con imágenes.
     */
    public function getPaginated($limit, $offset, $userId = null)
    {
        $sql = "SELECT 
                    p.*, 
                    u.nombre AS usuario_nombre,
                    c.nombre AS categoria_nombre
                FROM Productos p
                JOIN Usuario u ON p.usuario_id = u.id
                JOIN Categorias c ON p.categoria_id = c.id";

        if ($userId !== null) {
            $sql .= " WHERE p.usuario_id != :uid";
        }
        $sql .= " ORDER BY p.fecha_publicacion DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        if ($userId !== null) {
            $stmt->bindValue(":uid", $userId, PDO::PARAM_INT);
        }

        $stmt->bindValue(":limit", (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->attachImages($productos);
    }

    /* -----------------------------------------
       Obtener imágenes del producto
    ------------------------------------------ */

    /**
     * Obtiene todas las imágenes asociadas a un producto.
     *
     * @param int $productId ID del producto.
     * @return array Lista de imágenes ordenadas.
     */
    public function getImages($productId)
    {
        $sql = "SELECT url, orden 
                FROM Imagenes_prod 
                WHERE id_producto = :id 
                ORDER BY orden ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $productId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* -----------------------------------------
       Obtener producto por ID (detalle)
    ------------------------------------------ */

    /**
     * Obtiene un producto por su ID, incluyendo:
     * - categoría
     * - estado del producto
     * - estado de publicación
     * - datos del usuario
     * - imágenes asociadas
     *
     * @param int $id ID del producto.
     * @return array|null Datos del producto o null si no existe.
     */
    public function getById($id)
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre AS categoria,
                    ep.nombre AS estado_producto,
                    epu.nombre AS estado_publicacion,
                    u.nombre AS usuario_nombre,
                    u.email AS usuario_email
                FROM Productos p
                JOIN Categorias c ON p.categoria_id = c.id
                JOIN EstadoProducto ep ON p.estado_producto_id = ep.id
                JOIN EstadoPublicacion epu ON p.estado_publicacion_id = epu.id
                JOIN Usuario u ON p.usuario_id = u.id
                WHERE p.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            $producto["imagenes"] = $this->getImages($producto["id"]);
        }

        return $producto;
    }

    /* -----------------------------------------
       Obtener productos por usuario (perfil)
    ------------------------------------------ */

    /**
     * Obtiene productos de un usuario con paginación y búsqueda opcional.
     *
     * @param int $userId ID del usuario.
     * @param int $limit Límite de resultados.
     * @param int $offset Desplazamiento.
     * @param string $q Texto de búsqueda opcional.
     * @return array Lista de productos con imágenes.
     */
    public function getByUserPaginated(int $userId, int $limit, int $offset, string $q = ""): array
    {
        try {
            $sql = "SELECT 
                        p.id,
                        p.usuario_id,
                        p.titulo,
                        p.descripcion,
                        p.precio,
                        p.tipo_transaccion,
                        p.fecha_publicacion,
                        p.ubicacion,
                        c.nombre AS categoria,
                        ep.nombre AS estado_producto,
                        epu.nombre AS estado_publicacion
                    FROM Productos p
                    JOIN Categorias c ON p.categoria_id = c.id
                    JOIN EstadoProducto ep ON p.estado_producto_id = ep.id
                    JOIN EstadoPublicacion epu ON p.estado_publicacion_id = epu.id
                    WHERE p.usuario_id = :uid";

            if (!empty($q)) {
                $sql .= " AND p.titulo LIKE :q";
            }

            $sql .= " ORDER BY p.fecha_publicacion DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":uid", $userId, PDO::PARAM_INT);
            $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

            if (!empty($q)) {
                $stmt->bindValue(":q", "%$q%", PDO::PARAM_STR);
            }

            $stmt->execute();

            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->attachImages($productos);
        } catch (PDOException $e) {
            error_log("Error en Product::getByUserPaginated → " . $e->getMessage());
            return [];
        }
    }

    /* -----------------------------------------
       Crear producto
    ------------------------------------------ */

    /**
     * Crea un nuevo producto.
     *
     * @param array $data Datos del producto.
     * @return string|false ID del producto creado o false si falla.
     */
    public function create($data)
    {
        try {
            $sql = "INSERT INTO Productos 
                (usuario_id, categoria_id, titulo, descripcion, precio, estado_producto_id, tipo_transaccion, estado_publicacion_id, ubicacion)
                VALUES (:usuario_id, :categoria_id, :titulo, :descripcion, :precio, :estado_producto_id, :tipo_transaccion, :estado_publicacion_id, :ubicacion)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ":usuario_id" => $data["usuario_id"],
                ":categoria_id" => $data["categoria_id"],
                ":titulo" => $data["titulo"],
                ":descripcion" => $data["descripcion"],
                ":precio" => $data["precio"],
                ":estado_producto_id" => $data["estado_producto_id"],
                ":tipo_transaccion" => $data["tipo_transaccion"],
                ":estado_publicacion_id" => $data["estado_publicacion_id"],
                ":ubicacion" => $data["ubicacion"]
            ]);

            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en Product::create → " . $e->getMessage());
            return false;
        }
    }

    /* -----------------------------------------
       Actualizar producto
    ------------------------------------------ */

    /**
     * Actualiza un producto existente.
     *
     * @param int $id ID del producto.
     * @param array $data Datos actualizados.
     * @return bool True si se actualizó correctamente.
     */
    public function update($id, $data)
    {
        try {
            $sql = "UPDATE Productos SET 
                        categoria_id = :categoria_id,
                        titulo = :titulo,
                        descripcion = :descripcion,
                        precio = :precio,
                        estado_producto_id = :estado_producto_id,
                        tipo_transaccion = :tipo_transaccion,
                        estado_publicacion_id = :estado_publicacion_id,
                        ubicacion = :ubicacion
                    WHERE id = :id";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ":categoria_id" => $data["categoria_id"],
                ":titulo" => $data["titulo"],
                ":descripcion" => $data["descripcion"],
                ":precio" => $data["precio"],
                ":estado_producto_id" => $data["estado_producto_id"],
                ":tipo_transaccion" => $data["tipo_transaccion"],
                ":estado_publicacion_id" => $data["estado_publicacion_id"],
                ":ubicacion" => $data["ubicacion"],
                ":id" => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error en Product::update → " . $e->getMessage());
            return false;
        }
    }

    /* -----------------------------------------
       Eliminar producto
    ------------------------------------------ */

    /**
     * Elimina un producto por su ID.
     *
     * @param int $id ID del producto.
     * @return bool True si se eliminó correctamente.
     */
    public function delete($id)
    {
        $sql = "DELETE FROM Productos WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $id]);
    }

    /**
     * Elimina un producto junto con sus imágenes físicas y de BD.
     *
     * ⚠️ Este método mezcla lógica de BD con sistema de archivos.
     * No es testeable unitariamente sin mocks de filesystem.
     *
     * @param int $productId ID del producto.
     * @return bool True si todo se eliminó correctamente.
     */
    public function deleteWithImages(int $productId): bool
    {
        // 1. Obtener imágenes
        $sql = "SELECT url FROM Imagenes_prod WHERE id_producto = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":pid", $productId, PDO::PARAM_INT);
        $stmt->execute();
        $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Eliminar archivos físicos
        foreach ($imagenes as $img) {
            $ruta = __DIR__ . "/../" . $img["url"];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }

        // 3. Eliminar imágenes de BD
        $sql = "DELETE FROM Imagenes_prod WHERE id_producto = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":pid" => $productId]);

        // 4. Eliminar producto
        $sql = "DELETE FROM Productos WHERE id = :pid";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":pid" => $productId]);
    }

    /* -----------------------------------------
       Adjuntar imágenes a productos
    ------------------------------------------ */

    /**
     * Añade las imágenes correspondientes a cada producto.
     *
     * @param array $productos Lista de productos.
     * @return array Productos con sus imágenes.
     */
    private function attachImages(array $productos): array
    {
        foreach ($productos as &$p) {
            $p["imagenes"] = $this->getImages($p["id"]);
        }
        return $productos;
    }

    /* -----------------------------------------
       Contar productos por usuario
    ------------------------------------------ */

    /**
     * Cuenta cuántos productos tiene un usuario.
     *
     * @param int $userId ID del usuario.
     * @param string $q Búsqueda opcional.
     * @return int Número de productos.
     */
    public function countByUser(int $userId, string $q = ""): int
    {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM Productos 
                    WHERE usuario_id = :uid";

            if (!empty($q)) {
                $sql .= " AND titulo LIKE :q";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":uid", $userId, PDO::PARAM_INT);

            if (!empty($q)) {
                $stmt->bindValue(":q", "%$q%", PDO::PARAM_STR);
            }

            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en Product::countByUser → " . $e->getMessage());
            return 0;
        }
    }

    /* -----------------------------------------
       Búsqueda avanzada
    ------------------------------------------ */

    /**
     * Realiza una búsqueda avanzada con múltiples filtros:
     * - texto
     * - categoría
     * - estado del producto
     * - tipo de transacción
     * - rango de precio
     * - ubicación
     * - orden
     * - paginación
     *
     * @param array $filters Filtros aplicados.
     * @return array Lista de productos filtrados.
     */
    public function search(array $filters): array
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre AS categoria,
                    ep.nombre AS estado_producto,
                    epu.nombre AS estado_publicacion,
                    u.nombre AS usuario_nombre
                FROM Productos p
                JOIN Categorias c ON p.categoria_id = c.id
                JOIN EstadoProducto ep ON p.estado_producto_id = ep.id
                JOIN EstadoPublicacion epu ON p.estado_publicacion_id = epu.id
                JOIN Usuario u ON p.usuario_id = u.id
                WHERE 1=1";

        $params = [];

        // Búsqueda por texto
        if (!empty($filters["q"])) {
            $sql .= " AND (p.titulo LIKE :q OR p.descripcion LIKE :q)";
            $params[":q"] = "%" . $filters["q"] . "%";
        }

        // Categoría
        if (!empty($filters["categoria"])) {
            $sql .= " AND p.categoria_id = :categoria";
            $params[":categoria"] = $filters["categoria"];
        }

        // Estado del producto
        if (!empty($filters["estado_producto"])) {
            $sql .= " AND p.estado_producto_id = :estado_producto";
            $params[":estado_producto"] = $filters["estado_producto"];
        }

        // Tipo de transacción
        if (!empty($filters["tipo_transaccion"])) {
            $sql .= " AND p.tipo_transaccion = :tipo_transaccion";
            $params[":tipo_transaccion"] = $filters["tipo_transaccion"];
        }

        // Rango de precio
        if (!empty($filters["precio_min"])) {
            $sql .= " AND p.precio >= :precio_min";
            $params[":precio_min"] = $filters["precio_min"];
        }

        if (!empty($filters["precio_max"])) {
            $sql .= " AND p.precio <= :precio_max";
            $params[":precio_max"] = $filters["precio_max"];
        }

        // Ubicación
        if (!empty($filters["ubicacion"])) {
            $sql .= " AND p.ubicacion LIKE :ubicacion";
            $params[":ubicacion"] = "%" . $filters["ubicacion"] . "%";
        }

        // Orden
        switch ($filters["orden"] ?? "fecha_desc") {
            case "precio_asc":
                $sql .= " ORDER BY p.precio ASC";
                break;
            case "precio_desc":
                $sql .= " ORDER BY p.precio DESC";
                break;
            case "fecha_asc":
                $sql .= " ORDER BY p.fecha_publicacion ASC";
                break;
            default:
                $sql .= " ORDER BY p.fecha_publicacion DESC";
                break;
        }

        // Paginación
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(":limit", (int) $filters["limit"], PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int) $filters["offset"], PDO::PARAM_INT);

        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Añadir imágenes
        foreach ($productos as &$p) {
            $p["imagenes"] = $this->getImages($p["id"]);
        }

        return $productos;
    }

    /* -----------------------------------------
       Obtener imágenes ordenadas (para edición)
    ------------------------------------------ */

    /**
     * Obtiene imágenes de un producto ordenadas por su posición.
     *
     * @param int $productId ID del producto.
     * @return array Lista de imágenes.
     */
    public function getImagesByProduct(int $productId): array
    {
        $sql = "SELECT id, url, orden
                FROM Imagenes_prod
                WHERE id_producto = :pid
                ORDER BY orden ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":pid", $productId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* -----------------------------------------
       Insertar imagen nueva
    ------------------------------------------ */

    /**
     * Inserta una nueva imagen asociada a un producto.
     *
     * @param int $productId ID del producto.
     * @param string $url Ruta de la imagen.
     * @param int $orden Orden de visualización.
     * @return bool True si se insertó correctamente.
     */
    public function insertImage(int $productId, string $url, int $orden): bool
    {
        $sql = "INSERT INTO Imagenes_prod (id_producto, url, orden)
                VALUES (:pid, :url, :orden)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":pid" => $productId,
            ":url" => $url,
            ":orden" => $orden
        ]);
    }

    /* -----------------------------------------
       Eliminar imagen (BD)
    ------------------------------------------ */

    /**
     * Elimina una imagen asociada a un producto.
     *
     * No elimina el archivo físico, solo el registro en la base de datos.
     *
     * @param int $productId ID del producto.
     * @param string $url URL de la imagen.
     * @return bool True si se eliminó correctamente.
     */
    public function deleteImage(int $productId, string $url): bool
    {
        $sql = "DELETE FROM Imagenes_prod
                WHERE id_producto = :pid AND url = :url";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":pid" => $productId,
            ":url" => $url
        ]);
    }

    /* -----------------------------------------
       Actualizar orden de una imagen existente
    ------------------------------------------ */

    /**
     * Actualiza el orden de visualización de una imagen.
     *
     * @param int $imageId ID de la imagen.
     * @param int $orden Nuevo orden.
     * @return bool True si se actualizó correctamente.
     */
    public function updateImageOrder(int $imageId, int $orden): bool
    {
        $sql = "UPDATE Imagenes_prod
                SET orden = :orden
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":orden" => $orden,
            ":id" => $imageId
        ]);
    }


    /**
     * Cambia el estado de publicación de un producto.
     */
   /**
 * Cambia el estado de publicación del producto.
 */
public function cambiarEstadoPublicacion(int $productoId, string $estadoNombre): bool
{
    // Obtener ID del estado por nombre
    $sql = "UPDATE Productos 
            SET estado_publicacion_id = (
                SELECT id FROM EstadoPublicacion WHERE nombre = :estado
            )
            WHERE id = :id";

    $stmt = $this->conn->prepare($sql);

    return $stmt->execute([
        ':estado' => $estadoNombre,
        ':id'     => $productoId
    ]);
}

/**
 * Poner producto en estado 'pausado' (reservado).
 */
public function reservarProducto(int $productoId): bool
{
    return $this->cambiarEstadoPublicacion($productoId, 'pausado');
}


    /**
     * Marca la publicación como vendida.
     */
    public function marcarComoVendido(int $productoId): bool
    {
        return $this->cambiarEstadoPublicacion($productoId, 'vendido');
    }

    /**
     * Reactiva la publicación (por ejemplo, si se cancela la transacción).
     */
    public function reactivarPublicacion(int $productoId): bool
    {
        return $this->cambiarEstadoPublicacion($productoId, 'activo');
    }

}