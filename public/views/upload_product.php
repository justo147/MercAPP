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

                        <?php if (!empty($success)): ?>
                            <script>document.addEventListener('DOMContentLoaded', () => mostrarToast('<?= htmlspecialchars($success, ENT_QUOTES) ?>', 'success'));</script>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <script>document.addEventListener('DOMContentLoaded', () => mostrarToast('<?= htmlspecialchars($error, ENT_QUOTES) ?>', 'error'));</script>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="POST" enctype="multipart/form-data" data-unsaved-warning novalidate>

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del producto</label>
                                <input type="text" id="titulo" name="titulo" class="form-control" required
                                       maxlength="100" data-counter="titulo-counter">
                                <div class="d-flex justify-content-between mt-1">
                                    <div class="invalid-feedback">El título es obligatorio.</div>
                                    <small class="text-muted ms-auto" id="titulo-counter">0 / 100</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea id="descripcion" name="descripcion" class="form-control" rows="4"
                                          maxlength="2000" data-counter="desc-counter"></textarea>
                                <div class="d-flex justify-content-end mt-1">
                                    <small class="text-muted" id="desc-counter">0 / 2000</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio (€)</label>
                                <input type="number" id="precio" step="0.01" min="0" name="precio" class="form-control">
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
                                <label for="ubicacion" class="form-label">
                                    Ubicación
                                    <small class="text-muted fw-normal">— empieza a escribir para ver sugerencias</small>
                                </label>
                                <input type="text" id="ubicacion" name="ubicacion" class="form-control"
                                       placeholder="Ej: Sevilla, Calle Mayor 5 Madrid…"
                                       autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['ubicacion'] ?? '') ?>">
                                <input type="hidden" id="lat" name="lat" value="<?= htmlspecialchars($_POST['lat'] ?? '') ?>">
                                <input type="hidden" id="lon" name="lon" value="<?= htmlspecialchars($_POST['lon'] ?? '') ?>">
                                <div class="form-text">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Dirección normalizada gracias a OpenStreetMap.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="imagenesInput" class="form-label">Imágenes del producto</label>
                                <input type="file" name="imagenes[]" id="imagenesInput" class="form-control" multiple>
                                <small class="text-muted">Puedes arrastrar para cambiar el orden</small>

                                <div id="previewGrid" class="row g-3 mt-3"></div>
                                <input type="hidden" name="orden_imagenes" id="ordenImagenes">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success"
                                        data-loading-text="Publicando…">
                                    <i class="bi bi-upload me-1"></i> Publicar producto
                                </button>
                            </div>

                        </form>

                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="../js/uploadProduct.js"></script>
    <script src="../js/address_autocomplete.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initAddressAutocomplete === 'function') {
            initAddressAutocomplete('ubicacion', '<?= $BASE ?>/api/normalize_address.php', 'lat', 'lon');
        }

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

        // Validación cliente con Bootstrap
        const form = document.querySelector('form[novalidate]');
        if (form) {
            form.addEventListener('submit', e => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrarToast('Por favor, completa los campos obligatorios.', 'warning');
                } else {
                    localStorage.removeItem('draft_upload');
                }
                form.classList.add('was-validated');
            }, true);
        }

        // ── Autoguardado de borrador ──────────────────────────
        const DRAFT_KEY = 'draft_upload';
        const CAMPOS = ['titulo', 'descripcion', 'precio', 'categoria_id',
                        'estado_producto_id', 'tipo_transaccion', 'ubicacion'];

        function guardarBorrador() {
            const draft = {};
            CAMPOS.forEach(name => {
                const el = form?.querySelector(`[name="${name}"]`);
                if (el) draft[name] = el.value;
            });
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        }

        function restaurarBorrador() {
            const raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return;
            try {
                const draft = JSON.parse(raw);
                const tieneDatos = Object.values(draft).some(v => v && v.trim());
                if (!tieneDatos) return;

                CAMPOS.forEach(name => {
                    const el = form?.querySelector(`[name="${name}"]`);
                    if (el && draft[name]) el.value = draft[name];
                });

                // Actualizar contadores
                form?.querySelectorAll('[data-counter]').forEach(input => input.dispatchEvent(new Event('input')));

                mostrarToast('Se ha restaurado un borrador guardado anteriormente.', 'info');
            } catch { localStorage.removeItem(DRAFT_KEY); }
        }

        if (form) {
            // Restaurar al cargar (solo si el form está vacío)
            const tituloEl = form.querySelector('[name="titulo"]');
            if (tituloEl && !tituloEl.value) restaurarBorrador();

            // Guardar cada 5 segundos si hay cambios
            let dirtyDraft = false;
            form.addEventListener('input',  () => { dirtyDraft = true; });
            form.addEventListener('change', () => { dirtyDraft = true; });
            setInterval(() => { if (dirtyDraft) { guardarBorrador(); dirtyDraft = false; } }, 5000);
        }
    });
    </script>

</main>
</body>

</html>