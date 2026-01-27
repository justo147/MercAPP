<?php
require_once __DIR__ . '/../config/db.php';

class Product
{

    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
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

        foreach ($productos as &$p) {
            $p["imagenes"] = $this->getImages($p["id"]);
        }

        return $productos;
    }


    /* -----------------------------------------
       Obtener imágenes del producto
    ------------------------------------------ */
    private function getImages($productId)
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
       Obtener producto por ID producto(detalle)
    ------------------------------------------ */
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
            $producto = $this->attachImages([$producto])[0];
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

            //  Devolver el ID recién insertado
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en Product::create → " . $e->getMessage());
            return false;
        }
    }


    /* -----------------------------------------
       Actualizar producto
    ------------------------------------------ */
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
    public function delete($id)
    {
        $sql = "DELETE FROM Productos WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $id]);
    }

    private function attachImages(array $productos): array
    {
        foreach ($productos as &$p) {
            $p["imagenes"] = $this->getImages($p["id"]);
        }
        return $productos;
    }


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

}
