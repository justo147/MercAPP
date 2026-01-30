<?php
// Handler que procesará la subida del producto
require_once __DIR__ . '/../../controllers/handlers/upload_product_handler.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Subir producto - MercApp</title>

    <!-- Favicon -->
    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">

    <!-- Tema -->
    <script src="../js/theme.js" defer></script>
</head>

<body>
<main>
    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <div class="container py-5 sinFondo">
        <div class="row justify-content-center sinFondo">
            <div class="col-md-8 sinFondo">
                <div class="card shadow no-hover sinFondo">

                    <div class="card-header bg-primary text-white sinFondo">
                        <h1 class="no-style">Subir nuevo producto</h1>
                    </div>

                    <div class="card-body">

                        <!-- Mensajes -->
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="POST" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del producto</label>
                                <input type="text" id="titulo" name="titulo" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea id="descripcion" name="descripcion" class="form-control" rows="4"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio (€)</label>
                                <input type="number" id="precio" step="0.01" name="precio" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select id="categoria" name="categoria_id" class="form-select" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>">
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="estado_producto" class="form-label">Estado del producto</label>
                                <select id="estado_producto" name="estado_producto_id" class="form-select" required>
                                    <option value="">Selecciona un estado</option>
                                    <?php foreach ($estados_producto as $estado): ?>
                                        <option value="<?= $estado['id'] ?>">
                                            <?= htmlspecialchars($estado['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_transaccion" class="form-label">Tipo de transacción</label>
                                <select id="tipo_transaccion" name="tipo_transaccion" class="form-select" required>
                                    <option value="venta">Venta</option>
                                    <option value="intercambio">Intercambio</option>
                                    <option value="mixto">Mixto</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="ubicacion" class="form-label">Ubicación</label>
                                <input type="text" id="ubicacion" name="ubicacion" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label for="imagenesInput" class="form-label">Imágenes del producto</label>
                                <input type="file" name="imagenes[]" id="imagenesInput" class="form-control" multiple>
                                <small class="text-muted">Puedes arrastrar para cambiar el orden</small>

                                <div id="previewGrid" class="row g-3 mt-3"></div>
                                <input type="hidden" name="orden_imagenes" id="ordenImagenes">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Publicar producto</button>
                            </div>

                        </form>

                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="../js/uploadProduct.js"></script>

</main>
</body>

</html>