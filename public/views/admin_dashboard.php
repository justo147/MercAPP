<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';

// PROTECCIÓN ESTRICTA: Si no hay sesión o el rol no es admin, lo expulsamos.
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    // Ajusta esta ruta dependiendo de en qué carpeta guardes admin_dashboard.php
    header("Location: home.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - MercApp</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <script src="../js/admin.js" defer></script>
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <main class="container my-5 flex-grow-1">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
            <h1 class="h2 text-primary mb-3 mb-md-0">
                <i class="bi bi-shield-lock me-2"></i>Gestión de Usuarios
            </h1>
            
            <div class="input-group shadow-sm" style="max-width: 400px;">
                <input type="text" id="search-user" class="form-control" placeholder="Buscar por email o nombre...">
                <button class="btn btn-primary" id="btn-search"><i class="bi bi-search"></i></button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Registro</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-usuarios">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                    Cargando usuarios...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="paginacion-admin" class="d-flex justify-content-center"></div>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>