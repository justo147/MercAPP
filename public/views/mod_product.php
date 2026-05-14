<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';
// Handler de actualización
require_once __DIR__ . '/../../controllers/handlers/mod_product_handler.php';

$fatalError = "";

// Validar ID del producto
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    $fatalError = "ID de producto no válido";
} else {

    $productId = intval($_GET["id"]);
    $userId = $_SESSION["user_id"];

    // Conexión y modelo
    $db = new Database();
    $conn = $db->getConnection();
    $productModel = new Product($conn);

    // Obtener producto
    $producto = $productModel->getById($productId);

    if (!$producto) {
        $fatalError = "Producto no encontrado";
    } elseif ($producto["usuario_id"] != $userId) {
        $fatalError = "No tienes permiso para editar este producto";
    } else {
        // Cargar selects solo si todo es correcto
        $stmt = $conn->query("SELECT * FROM Categorias ORDER BY nombre");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->query("SELECT * FROM EstadoProducto ORDER BY nombre");
        $estados_producto = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar producto - MercApp</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">

    <script src="../js/theme.js" defer></script>
</head>

<body>

    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <div class="container py-5 sinFondo">
        <div class="row justify-content-center sinFondo">
            <div class="col-md-8 sinFondo">
                <div class="card shadow no-hover sinFondo">

                    <div class="card-header bg-warning text-dark sinFondo">
                        <h1 class="no-style">Editar producto</h1>
                    </div>

                    <div class="card-body">

                        <?php if (!empty($success)): ?>
                            <script>document.addEventListener('DOMContentLoaded', () => mostrarToast('<?= htmlspecialchars($success, ENT_QUOTES) ?>', 'success'));</script>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <script>document.addEventListener('DOMContentLoaded', () => mostrarToast('<?= htmlspecialchars($error, ENT_QUOTES) ?>', 'error'));</script>
                        <?php endif; ?>

                        <?php if (!empty($fatalError)): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($fatalError) ?>
                            </div>
                            <a href="<?=$BASE?>/public/views/profile.php?id=<?= $_SESSION["user_id"] ?>" class="btn btn-primary mt-3">
                                Volver a mi perfil
                            </a>
                        <?php else: ?>

                        <!-- Formulario -->
                        <form method="POST" enctype="multipart/form-data" data-unsaved-warning novalidate>

                            <input type="hidden" name="id" value="<?= $producto["id"] ?>">

                            <div class="mb-3">
                                <label class="form-label">Título</label>
                                <input type="text" name="titulo" id="titulo" class="form-control"
                                    value="<?= htmlspecialchars($producto["titulo"]) ?>"
                                    required maxlength="100" data-counter="titulo-counter">
                                <div class="d-flex justify-content-between mt-1">
                                    <div class="invalid-feedback">El título es obligatorio.</div>
                                    <small class="text-muted ms-auto" id="titulo-counter">0 / 100</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" id="descripcion" class="form-control" rows="4"
                                          maxlength="2000" data-counter="desc-counter"><?= htmlspecialchars($producto["descripcion"]) ?></textarea>
                                <div class="d-flex justify-content-end mt-1">
                                    <small class="text-muted" id="desc-counter">0 / 2000</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio (€)</label>
                                <input type="number" step="0.01" name="precio" class="form-control"
                                    value="<?= htmlspecialchars($producto["precio"]) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria_id" class="form-select" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"
                                            <?= $cat['id'] == $producto["categoria_id"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Estado del producto</label>
                                <select name="estado_producto_id" class="form-select" required>
                                    <option value="">Selecciona un estado</option>
                                    <?php foreach ($estados_producto as $estado): ?>
                                        <option value="<?= $estado['id'] ?>"
                                            <?= $estado['id'] == $producto["estado_producto_id"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars($estado['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de transacción</label>
                                <select name="tipo_transaccion" class="form-select" required>
                                    <option value="venta" <?= $producto["tipo_transaccion"] == "venta" ? "selected" : "" ?>>Venta</option>
                                    <option value="intercambio" <?= $producto["tipo_transaccion"] == "intercambio" ? "selected" : "" ?>>Intercambio</option>
                                    <option value="mixto" <?= $producto["tipo_transaccion"] == "mixto" ? "selected" : "" ?>>Mixto</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ubicación</label>
                                <input type="text" name="ubicacion" class="form-control"
                                    value="<?= htmlspecialchars($producto["ubicacion"]) ?>">
                            </div>

                            <!-- Imágenes actuales -->
                            <div class="mb-3">
                                <label class="form-label">Imágenes actuales</label>
                                <div class="row g-3">
                                    <?php foreach ($producto["imagenes"] as $img): ?>
                                        <div class="col-4 text-center">
                                            <img src="<?= $BASE ?>/<?= htmlspecialchars($img["url"]) ?>"
                                                class="img-fluid rounded mb-2 img-actual"
                                                data-url="<?= htmlspecialchars($img["url"]) ?>"
                                                data-orden="<?= intval($img["orden"]) ?>"
                                                style="height:120px; object-fit:cover;"
                                                onerror="this.onerror=null;this.src='<?= $BASE ?>/public/img/default.jpg'">
                                            <div>
                                                <input type="checkbox" name="delete_images[]" value="<?= $img["url"] ?>">
                                                <small>Eliminar</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>


                            <!-- Añadir nuevas imágenes -->
                            <div class="mb-3">
                                <label class="form-label">Añadir nuevas imágenes</label>
                                <input type="file" name="imagenes[]" id="imagenesInput" class="form-control" multiple>

                                <small class="text-muted">Puedes arrastrar para cambiar el orden</small>

                                <div id="previewGrid" class="row g-3 mt-3"></div>
                                <input type="hidden" name="orden_imagenes" id="ordenImagenes">
                            </div>


                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning"
                                        data-loading-text="Guardando…">
                                    <i class="bi bi-floppy me-1"></i> Guardar cambios
                                </button>
                            </div>

                        </form>

                        <?php endif; ?>

                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="../js/modProduct.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Contadores de caracteres
        document.querySelectorAll('[data-counter]').forEach(input => {
            const counterId = input.dataset.counter;
            const counter   = document.getElementById(counterId);
            if (!counter) return;
            const max = input.maxLength || 0;
            const update = () => {
                const len = input.value.length;
                counter.textContent = `${len} / ${max}`;
                counter.classList.toggle('text-danger', max > 0 && len >= max * 0.9);
            };
            input.addEventListener('input', update);
            update();
        });

        // Validación cliente
        const form = document.querySelector('form[novalidate]');
        if (form) {
            form.addEventListener('submit', e => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrarToast('Por favor, completa los campos obligatorios.', 'warning');
                } else {
                    const prodId = form.querySelector('[name="id"]')?.value;
                    if (prodId) localStorage.removeItem(`draft_mod_${prodId}`);
                }
                form.classList.add('was-validated');
            }, true);
        }

        // ── Autoguardado de borrador ──────────────────────────
        const prodId   = form?.querySelector('[name="id"]')?.value;
        const DRAFT_KEY = prodId ? `draft_mod_${prodId}` : null;
        const CAMPOS = ['titulo', 'descripcion', 'precio', 'categoria_id',
                        'estado_producto_id', 'tipo_transaccion', 'ubicacion'];

        if (form && DRAFT_KEY) {
            let dirtyDraft = false;

            function guardarBorrador() {
                const draft = {};
                CAMPOS.forEach(name => {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el) draft[name] = el.value;
                });
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            }

            // Restaurar solo si hay cambios no guardados (comparar con valores actuales)
            const raw = localStorage.getItem(DRAFT_KEY);
            if (raw) {
                try {
                    const draft = JSON.parse(raw);
                    const diferente = CAMPOS.some(name => {
                        const el = form.querySelector(`[name="${name}"]`);
                        return el && draft[name] !== undefined && draft[name] !== el.value;
                    });
                    if (diferente) {
                        mostrarToast('Hay cambios sin guardar de una sesión anterior. Restaurando…', 'info');
                        CAMPOS.forEach(name => {
                            const el = form.querySelector(`[name="${name}"]`);
                            if (el && draft[name] !== undefined) el.value = draft[name];
                        });
                        form.querySelectorAll('[data-counter]').forEach(inp => inp.dispatchEvent(new Event('input')));
                    }
                } catch { localStorage.removeItem(DRAFT_KEY); }
            }

            form.addEventListener('input',  () => { dirtyDraft = true; });
            form.addEventListener('change', () => { dirtyDraft = true; });
            setInterval(() => { if (dirtyDraft) { guardarBorrador(); dirtyDraft = false; } }, 5000);
        }
    });
    </script>

</body>

</html>
