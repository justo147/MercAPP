<?php

require_once __DIR__ . '/../core/Controller.php';

class PageController extends Controller
{
    public function help(array $params = []): void
    {
        $this->render('help.html.twig', []);
    }

    public function docs(array $params = []): void
    {
        $this->render('docs.html.twig', []);
    }

    public function favorites(array $params = []): void
    {
        $this->requireAuth();

        $stmt = $this->conn->prepare("
            SELECT p.id, p.titulo, p.precio,
                   (SELECT url FROM Imagenes_prod WHERE id_producto = p.id ORDER BY orden ASC LIMIT 1) AS imagen
            FROM Favoritos f
            JOIN Productos p ON p.id = f.producto_id
            WHERE f.usuario_id = :uid
        ");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('my_favorites.html.twig', [
            'favoritos' => $favoritos,
        ]);
    }

    public function transactions(array $params = []): void
    {
        $this->requireAuth();

        $this->render('my_transactions.html.twig', [
            'usuarioId' => intval($_SESSION['user_id']),
        ]);
    }

    public function wishlist(array $params = []): void
    {
        $this->requireAuth();

        $categorias      = $this->conn->query('SELECT id, nombre FROM Categorias ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
        $estadosProducto = $this->conn->query('SELECT id, nombre FROM EstadoProducto ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        $this->render('my_wishlist.html.twig', [
            'categorias'      => $categorias,
            'estadosProducto' => $estadosProducto,
        ]);
    }

    public function following(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/User.php';

        $userId    = (int) $_SESSION['user_id'];
        $userModel = new User($this->conn);

        $siguiendo    = $userModel->obtenerSeguidos($userId);
        $siguiendoIds = array_column($siguiendo, 'id');

        $sugerencias = [];
        try {
            $excludeSql = '';
            $allParams  = [$userId];
            if (!empty($siguiendoIds)) {
                $ph         = implode(',', array_fill(0, count($siguiendoIds), '?'));
                $excludeSql = "AND u.id NOT IN ($ph)";
                $allParams  = array_merge($allParams, $siguiendoIds);
            }
            $stmtSug = $this->conn->prepare("
                SELECT u.id, u.nombre, u.apellidos, u.foto_perfil, COUNT(p.id) AS total_productos
                FROM   Usuario u
                JOIN   Productos p          ON p.usuario_id      = u.id
                JOIN   EstadoPublicacion ep ON ep.id             = p.estado_publicacion_id AND ep.nombre = 'activo'
                WHERE  u.id != ? $excludeSql
                GROUP  BY u.id HAVING total_productos > 0
                ORDER  BY total_productos DESC LIMIT 8
            ");
            $stmtSug->execute($allParams);
            $sugerencias = $stmtSug->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // silencioso
        }

        $this->render('followers_products.html.twig', [
            'siguiendo'   => $siguiendo,
            'sugerencias' => $sugerencias,
            'usuarioId'   => $userId,
        ]);
    }
}
