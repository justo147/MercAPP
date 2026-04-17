<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
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

    <style>
        .stat-card { border-left: 4px solid; transition: transform .15s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card.usuarios { border-color: #0d6efd; }
        .stat-card.productos { border-color: #198754; }
        .stat-card.reportes  { border-color: #dc3545; }
        .stat-card .stat-number { font-size: 2rem; font-weight: 700; line-height: 1; }
        .admin-nav .nav-link { color: #495057; font-weight: 500; padding: .6rem 1rem; border-radius: .375rem; }
        .admin-nav .nav-link.active { background: #0d6efd; color: #fff; }
        .admin-nav .nav-link i { margin-right: .4rem; }
        .table th { white-space: nowrap; }
        .badge-pendiente { background: #ffc107; color: #000; }
        .badge-revisado  { background: #0d6efd; color: #fff; }
        .badge-rechazado { background: #6c757d; color: #fff; }
        .product-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <main class="container-xl my-4 flex-grow-1">

        <!-- Cabecera -->
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-shield-lock fs-3 text-primary me-2"></i>
            <div>
                <h1 class="h3 mb-0 fw-bold">Panel de Administración</h1>
                <small class="text-muted">MercApp · Control total de la plataforma</small>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="row g-3 mb-4" id="stats-row">
            <!-- Usuarios -->
            <div class="col-6 col-md-3">
                <div class="card stat-card usuarios shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Usuarios totales</p>
                                <div class="stat-number text-primary" id="stat-usuarios-total">—</div>
                            </div>
                            <i class="bi bi-people-fill fs-2 text-primary opacity-25"></i>
                        </div>
                        <div class="mt-2 small text-muted">
                            <span class="text-success me-2"><i class="bi bi-check-circle"></i> <span id="stat-usuarios-activos">—</span> activos</span>
                            <span class="text-warning"><i class="bi bi-pause-circle"></i> <span id="stat-usuarios-suspendidos">—</span> suspendidos</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Productos -->
            <div class="col-6 col-md-3">
                <div class="card stat-card productos shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Productos totales</p>
                                <div class="stat-number text-success" id="stat-productos-total">—</div>
                            </div>
                            <i class="bi bi-box-seam fs-2 text-success opacity-25"></i>
                        </div>
                        <div class="mt-2 small text-muted">
                            <span class="text-success me-2"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> <span id="stat-productos-activos">—</span> activos</span>
                            <span class="text-secondary"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> <span id="stat-productos-vendidos">—</span> vendidos</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Reportes pendientes -->
            <div class="col-6 col-md-3">
                <div class="card stat-card reportes shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Reportes pendientes</p>
                                <div class="stat-number text-danger" id="stat-reportes-pendientes">—</div>
                            </div>
                            <i class="bi bi-flag-fill fs-2 text-danger opacity-25"></i>
                        </div>
                        <div class="mt-2 small text-muted">
                            Total: <span id="stat-reportes-total">—</span> reportes registrados
                        </div>
                    </div>
                </div>
            </div>
            <!-- Admins -->
            <div class="col-6 col-md-3">
                <div class="card stat-card shadow-sm h-100" style="border-color:#6f42c1">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Productos pausados</p>
                                <div class="stat-number" style="color:#6f42c1" id="stat-productos-pausados">—</div>
                            </div>
                            <i class="bi bi-pause-circle fs-2 opacity-25" style="color:#6f42c1"></i>
                        </div>
                        <div class="mt-2 small text-muted">
                            Eliminados: <span id="stat-usuarios-eliminados">—</span> usuarios
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación de tabs -->
        <ul class="nav admin-nav mb-3 flex-nowrap overflow-auto" id="admin-tabs">
            <li class="nav-item">
                <button class="nav-link active" data-tab="usuarios" onclick="cambiarTab('usuarios')">
                    <i class="bi bi-people"></i> Usuarios
                </button>
            </li>
            <li class="nav-item ms-1">
                <button class="nav-link" data-tab="productos" onclick="cambiarTab('productos')">
                    <i class="bi bi-box-seam"></i> Productos
                </button>
            </li>
            <li class="nav-item ms-1">
                <button class="nav-link position-relative" data-tab="reportes" onclick="cambiarTab('reportes')">
                    <i class="bi bi-flag"></i> Reportes
                    <span id="badge-reportes" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"></span>
                </button>
            </li>
        </ul>

        <!-- ============================================================
             TAB: USUARIOS
        ============================================================ -->
        <div id="tab-usuarios">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-people me-1 text-primary"></i>Gestión de Usuarios</h5>
                <div class="input-group shadow-sm" style="max-width: 380px;">
                    <input type="text" id="search-user" class="form-control" placeholder="Buscar por email o nombre...">
                    <button class="btn btn-primary" id="btn-search-user"><i class="bi bi-search"></i></button>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
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
                                <tr><td colspan="7" class="text-center py-5 text-muted">
                                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                    Cargando usuarios...
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="paginacion-usuarios" class="d-flex justify-content-center"></div>
        </div>

        <!-- ============================================================
             TAB: PRODUCTOS
        ============================================================ -->
        <div id="tab-productos" class="d-none">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-1 text-success"></i>Gestión de Productos</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <select id="filter-estado-producto" class="form-select form-select-sm" style="width:auto">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="pausado">Pausados</option>
                        <option value="vendido">Vendidos</option>
                    </select>
                    <div class="input-group input-group-sm shadow-sm" style="max-width: 280px;">
                        <input type="text" id="search-product" class="form-control" placeholder="Título, vendedor...">
                        <button class="btn btn-success" id="btn-search-product"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Vendedor</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-productos">
                                <tr><td colspan="9" class="text-center py-5 text-muted">
                                    <div class="spinner-border text-success spinner-border-sm me-2" role="status"></div>
                                    Cargando productos...
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="paginacion-productos" class="d-flex justify-content-center"></div>
        </div>

        <!-- ============================================================
             TAB: REPORTES
        ============================================================ -->
        <div id="tab-reportes" class="d-none">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-flag me-1 text-danger"></i>Gestión de Reportes</h5>
                <div class="d-flex gap-2">
                    <select id="filter-estado-reporte" class="form-select form-select-sm" style="width:auto">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="revisado">Revisados</option>
                        <option value="rechazado">Rechazados</option>
                    </select>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Reportado por</th>
                                    <th>Propietario</th>
                                    <th>Motivo</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-reportes">
                                <tr><td colspan="8" class="text-center py-5 text-muted">
                                    <div class="spinner-border text-danger spinner-border-sm me-2" role="status"></div>
                                    Cargando reportes...
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="paginacion-reportes" class="d-flex justify-content-center"></div>
        </div>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
