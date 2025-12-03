<?php
require_once __DIR__ . '/../../controllers/handlers/detail_account_handlers.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalles de la cuenta - MercApp</title>
    <!-- Favicon (AÑADIDO) -->
    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- RUTAS CSS CORREGIDAS -->
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css"> <!-- AÑADIR si usas este CSS -->
    
    <!-- RUTA JS CORREGIDA -->
    <script src="../js/theme.js" defer></script>
</head>

<body>
    <!-- Navbar (RUTA CORREGIDA) -->
    <?php
    $showSearch = false;
    include("navbar.php"); ?>

    <div class="container py-5 sinFondo">
        <div class="row justify-content-center sinFondo">
            <div class="col-md-8 sinFondo">
                <div class="card shadow no-hover sinFondo">
                    <div class="card-header bg-primary text-white sinFondo">
                        <h4 class="no-style">Detalles de la cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['updated'])): ?>
                            <div class="alert alert-success">Datos actualizados correctamente.</div>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <div class="d-flex justify-content-center">
                                    <?php if (!empty($user['foto_perfil'])): ?>
                                        <img src="<?= htmlspecialchars($user['foto_perfil']) ?>"
                                            class="rounded-circle mb-3" width="120" height="120" alt="Foto de perfil">
                                    <?php else: ?>
                                        <i class="rounded-circle mb-3 bi bi-people"
                                            style="font-size:120px;"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="foto" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>