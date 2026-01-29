<?php
require_once __DIR__ . '/../config/db.php';

class Product {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /* -----------------------------------------
       Obtener productos paginados (Home)
    ------------------------------------------ */
    public function getPaginated($limit, $offset) {
        $sql = "SELECT 
                    p.*, 
                    u.nombre AS usuario_nombre,
                    c.nombre AS categoria_nombre
                FROM Productos p
                JOIN Usuario u ON p.usuario_id = u.id
                JOIN Categorias c ON p.categoria_id = c.id
                ORDER BY p.fecha_publicacion DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->attachImages($productos);
    }

    /* -----------------------------------------
       Obtener imágenes del producto
    ------------------------------------------ */
    public function getImages($productId) {
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
    public function getById($id) {
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
    public function getByUserPaginated(int $userId, int $limit, int $offset): array {
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
                    WHERE p.usuario_id = :uid
                    ORDER BY p.fecha_publicacion DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":uid", $userId, PDO::PARAM_INT);
            $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
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
    public function create($data) {
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
    public function update($id, $data) {
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
    public function delete($id) {
        $sql = "DELETE FROM Productos WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $id]);
    }

    /* -----------------------------------------
       Adjuntar imágenes a productos
    ------------------------------------------ */
    private function attachImages(array $productos): array {
        foreach ($productos as &$p) {
            $p["imagenes"] = $this->getImages($p["id"]);
        }
        return $productos;
    }

    /* -----------------------------------------
       Contar productos por usuario
    ------------------------------------------ */
    public function countByUser(int $userId): int {
        try {
            $sql = "SELECT COUNT(*) FROM Productos WHERE usuario_id = :uid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":uid", $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en Product::countByUser → " . $e->getMessage());
            return 0;
        }
    }


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

    /* -----------------------------------------
       FILTROS DINÁMICOS
    ------------------------------------------ */

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

    /* -----------------------------------------
       ORDENACIÓN SEGURA
    ------------------------------------------ */

    $ordenesPermitidos = [
        "fecha_desc" => "p.fecha_publicacion DESC",
        "fecha_asc"  => "p.fecha_publicacion ASC",
        "precio_asc" => "p.precio ASC",
        "precio_desc"=> "p.precio DESC"
    ];

    if (!empty($filters["orden"]) && isset($ordenesPermitidos[$filters["orden"]])) {
        $sql .= " ORDER BY " . $ordenesPermitidos[$filters["orden"]];
    } else {
        $sql .= " ORDER BY p.fecha_publicacion DESC";
    }

    /* -----------------------------------------
       PAGINACIÓN
    ------------------------------------------ */

    $limit = $filters["limit"] ?? 12;
    $offset = $filters["offset"] ?? 0;

    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);

    $stmt->execute();

    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $this->attachImages($productos);
}

}
