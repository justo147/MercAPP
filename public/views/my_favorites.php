<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/db.php';

// Conexión a la base de datos (TU FORMA)
$db = new Database();
$conn = $db->getConnection();

// Obtenemos los favoritos del usuario
$sql = "SELECT 
            p.id, 
            p.titulo, 
            p.precio,
            (SELECT url 
             FROM Imagenes_prod 
             WHERE id_producto = p.id 
             ORDER BY orden ASC 
             LIMIT 1) AS imagen
        FROM Favoritos f
        JOIN Productos p ON p.id = f.producto_id
        WHERE f.usuario_id = :uid";

$stmt = $conn->prepare($sql);
$stmt->execute([":uid" => $_SESSION["user_id"]]);
$favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos</title>

    <!-- Favicon -->
    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- CSS -->
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

    <!-- Navbar -->
    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <main class="container py-4">

        <h2 class="mb-4 text-center">
            <i class="bi bi-heart me-2"></i> Mis Favoritos
        </h2>


        <?php if (empty($favoritos)): ?>
            <div class="alert alert-info text-center">
                No tienes productos en favoritos todavía.
            </div>

        <?php else: ?>
            <div class="row g-4">

                <?php foreach ($favoritos as $p): ?>
                    <div class="col-md-3">
                        <a href="detail_product.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                            <div class="card shadow-sm">

                                <img src="/<?= htmlspecialchars($p['imagen'] ?? 'uploads/products/default.jpg') ?>"
                                    class="card-img-top"
                                    style="height: 200px; object-fit: cover;">

                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($p['titulo']) ?></h5>
                                    <p class="text-success fw-bold"><?= number_format($p['precio'], 2) ?> €</p>
                                </div>

                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>