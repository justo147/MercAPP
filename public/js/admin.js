/* ============================================================
   ADMIN.JS — Panel de Administración MercApp
   Tabs: Usuarios | Productos | Reportes
   ============================================================ */

// ── Estado global ────────────────────────────────────────────
const state = {
    usuarios:  { page: 1, limit: 10, q: "" },
    productos: { page: 1, limit: 10, q: "", estado: "" },
    reportes:  { page: 1, limit: 10, estado: "" },
    tabActual: "usuarios"
};

// ── Bootstrap ────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    cargarEstadisticas();
    cargarUsuarios();

    // ── Buscador usuarios ────────────────────────────────────
    const btnBuscarUser  = document.getElementById("btn-search-user");
    const inputBuscarUser = document.getElementById("search-user");

    btnBuscarUser?.addEventListener("click", () => {
        state.usuarios.q    = inputBuscarUser.value.trim();
        state.usuarios.page = 1;
        cargarUsuarios();
    });
    inputBuscarUser?.addEventListener("keypress", e => {
        if (e.key === "Enter") btnBuscarUser.click();
    });

    // ── Buscador productos ───────────────────────────────────
    const btnBuscarProd  = document.getElementById("btn-search-product");
    const inputBuscarProd = document.getElementById("search-product");
    const filtroEstadoProd = document.getElementById("filter-estado-producto");

    btnBuscarProd?.addEventListener("click", () => {
        state.productos.q    = inputBuscarProd.value.trim();
        state.productos.page = 1;
        cargarProductos();
    });
    inputBuscarProd?.addEventListener("keypress", e => {
        if (e.key === "Enter") btnBuscarProd.click();
    });
    filtroEstadoProd?.addEventListener("change", () => {
        state.productos.estado = filtroEstadoProd.value;
        state.productos.page   = 1;
        cargarProductos();
    });

    // ── Filtro reportes ──────────────────────────────────────
    const filtroEstadoRep = document.getElementById("filter-estado-reporte");
    filtroEstadoRep?.addEventListener("change", () => {
        state.reportes.estado = filtroEstadoRep.value;
        state.reportes.page   = 1;
        cargarReportes();
    });
});

// ── Cambio de Tab ────────────────────────────────────────────
window.cambiarTab = function (tab) {
    state.tabActual = tab;

    // Actualizar botones de navegación
    document.querySelectorAll("#admin-tabs .nav-link").forEach(btn => {
        btn.classList.toggle("active", btn.dataset.tab === tab);
    });

    // Mostrar / ocultar secciones
    ["usuarios", "productos", "reportes"].forEach(t => {
        document.getElementById(`tab-${t}`)?.classList.toggle("d-none", t !== tab);
    });

    // Cargar datos la primera vez que se abre cada tab
    if (tab === "productos" && !state.productos._loaded) {
        state.productos._loaded = true;
        cargarProductos();
    }
    if (tab === "reportes" && !state.reportes._loaded) {
        state.reportes._loaded = true;
        cargarReportes();
    }
};

// ── ESTADÍSTICAS ─────────────────────────────────────────────
async function cargarEstadisticas() {
    try {
        const res  = await fetch(`${BASE}/api/admin_stats.php`);
        const json = await res.json();
        if (!json.success) return;

        const { usuarios, productos, reportes } = json.stats;

        setText("stat-usuarios-total",       usuarios.total);
        setText("stat-usuarios-activos",      usuarios.activos);
        setText("stat-usuarios-suspendidos",  usuarios.suspendidos);
        setText("stat-usuarios-eliminados",   usuarios.eliminados);

        setText("stat-productos-total",   productos.total);
        setText("stat-productos-activos", productos.activos);
        setText("stat-productos-vendidos",productos.vendidos);
        setText("stat-productos-pausados",productos.pausados);

        setText("stat-reportes-total",     reportes.total);
        setText("stat-reportes-pendientes",reportes.pendientes);

        // Mostrar badge si hay reportes pendientes
        const badge = document.getElementById("badge-reportes");
        if (badge && reportes.pendientes > 0) {
            badge.textContent = reportes.pendientes;
            badge.classList.remove("d-none");
        }
    } catch (err) {
        console.error("Error cargando estadísticas:", err);
    }
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = value ?? "0";
    el.classList.remove("placeholder-glow");
    el.querySelectorAll(".placeholder").forEach(p => p.remove());
}

// ── USUARIOS ─────────────────────────────────────────────────
async function cargarUsuarios() {
    const tbody     = document.getElementById("tabla-usuarios");
    const paginador = document.getElementById("paginacion-usuarios");

    tbody.innerHTML = spinnerRow(7);

    try {
        const params = new URLSearchParams({
            page:  state.usuarios.page,
            limit: state.usuarios.limit,
            q:     state.usuarios.q
        });
        const res  = await fetch(`${BASE}/api/admin_get_users.php?${params}`);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = errorRow(7, json.error || "Error cargando usuarios");
            paginador.innerHTML = "";
            return;
        }

        if (!json.data.length) {
            tbody.innerHTML = emptyRow(7, "No se encontraron usuarios.");
            paginador.innerHTML = "";
            return;
        }

        tbody.innerHTML = "";
        json.data.forEach(u => tbody.appendChild(crearFilaUsuario(u)));
        renderPaginacion(paginador, json.total, state.usuarios, cargarUsuarios);

    } catch (err) {
        console.error(err);
        tbody.innerHTML = errorRow(7, "Error de conexión.");
    }
}

function crearFilaUsuario(u) {
    const tr = document.createElement("tr");

    const badgeEstado = { activo: "bg-success", suspendido: "bg-warning text-dark", eliminado: "bg-danger" };
    const badgeRol    = u.rol === "admin" ? "bg-primary" : "bg-secondary";

    let acciones;
    if (u.rol === "admin") {
        acciones = `<span class="text-muted small fw-bold"><i class="bi bi-shield-check text-primary"></i> Protegido</span>`;
    } else {
        acciones = `
            <div class="d-flex gap-1 justify-content-center flex-wrap">
                <button class="btn btn-sm btn-outline-success" onclick="cambiarEstadoUsuario(${u.id},'activo')" title="Activar">
                    <i class="bi bi-play-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="cambiarEstadoUsuario(${u.id},'suspendido')" title="Suspender">
                    <i class="bi bi-pause-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="cambiarEstadoUsuario(${u.id},'eliminado')" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="cambiarRol(${u.id},'admin')" title="Hacer admin">
                    <i class="bi bi-shield-plus"></i>
                </button>
            </div>`;
    }

    tr.innerHTML = `
        <td class="fw-bold text-muted">#${u.id}</td>
        <td>
            <div class="fw-semibold">${esc(u.nombre)} ${esc(u.apellidos || '')}</div>
            <div class="small text-muted">${esc(u.telefono || 'Sin teléfono')}</div>
        </td>
        <td>${esc(u.email)}</td>
        <td>${new Date(u.fecha_registro).toLocaleDateString('es-ES')}</td>
        <td><span class="badge ${badgeRol}">${u.rol.toUpperCase()}</span></td>
        <td><span class="badge ${badgeEstado[u.estado] || 'bg-secondary'}">${u.estado.toUpperCase()}</span></td>
        <td class="text-center">${acciones}</td>`;
    return tr;
}

window.cambiarEstadoUsuario = function (id, nuevoEstado) {
    const etiquetas = { activo: "activar", suspendido: "suspender", eliminado: "eliminar" };
    adminConfirm({
        titulo:   `¿${etiquetas[nuevoEstado] ?? nuevoEstado} usuario #${id}?`,
        mensaje:  `El usuario pasará al estado "${nuevoEstado}".`,
        btnTexto: "Confirmar",
        btnClass: nuevoEstado === "eliminado" ? "btn-danger" : nuevoEstado === "suspendido" ? "btn-warning" : "btn-success",
        onConfirm: async () => {
            try {
                const res  = await fetch(`${BASE}/api/admin_update_status.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id, estado: nuevoEstado })
                });
                const json = await res.json();
                if (json.success) {
                    cargarUsuarios();
                    cargarEstadisticas();
                    adminToast(`Usuario #${id} marcado como ${nuevoEstado}.`, "success");
                } else {
                    adminToast("Error: " + json.error);
                }
            } catch {
                adminToast("Error de conexión.");
            }
        }
    });
};

window.cambiarRol = function (id, nuevoRol) {
    adminConfirm({
        titulo:   `¿Hacer administrador al usuario #${id}?`,
        mensaje:  "Esta acción es reversible desde la base de datos.",
        btnTexto: "Hacer admin",
        btnClass: "btn-primary",
        onConfirm: async () => {
            try {
                const res  = await fetch(`${BASE}/api/admin_change_role.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id, rol: nuevoRol })
                });
                const json = await res.json();
                if (json.success) {
                    cargarUsuarios();
                    adminToast(`Usuario #${id} es ahora administrador.`, "success");
                } else {
                    adminToast("Error: " + json.error);
                }
            } catch {
                adminToast("Error de conexión.");
            }
        }
    });
};

// ── PRODUCTOS ─────────────────────────────────────────────────
async function cargarProductos() {
    const tbody     = document.getElementById("tabla-productos");
    const paginador = document.getElementById("paginacion-productos");

    tbody.innerHTML = spinnerRow(9);

    try {
        const params = new URLSearchParams({
            page:   state.productos.page,
            limit:  state.productos.limit,
            q:      state.productos.q,
            estado: state.productos.estado
        });
        const res  = await fetch(`${BASE}/api/admin_get_products.php?${params}`);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = errorRow(9, json.error || "Error cargando productos");
            paginador.innerHTML = "";
            return;
        }

        if (!json.data.length) {
            tbody.innerHTML = emptyRow(9, "No se encontraron productos.");
            paginador.innerHTML = "";
            return;
        }

        tbody.innerHTML = "";
        json.data.forEach(p => tbody.appendChild(crearFilaProducto(p)));
        renderPaginacion(paginador, json.total, state.productos, cargarProductos);

    } catch (err) {
        console.error(err);
        tbody.innerHTML = errorRow(9, "Error de conexión.");
    }
}

function crearFilaProducto(p) {
    const tr = document.createElement("tr");

    const estadoColors = { activo: "bg-success", pausado: "bg-warning text-dark", vendido: "bg-secondary" };
    const badgeEstado  = estadoColors[p.estado_publicacion] || "bg-secondary";

    const precio = p.precio ? `${parseFloat(p.precio).toFixed(2)} €` : `<span class="text-muted small">Trueque</span>`;

    const imagen = p.imagen
        ? `<img src="${BASE}/${esc(p.imagen)}" class="product-thumb" alt="img">`
        : `<span class="text-muted"><i class="bi bi-image fs-4"></i></span>`;

    tr.innerHTML = `
        <td class="fw-bold text-muted">#${p.id}</td>
        <td>${imagen}</td>
        <td>
            <div class="fw-semibold">${esc(p.titulo)}</div>
            <div class="small text-muted">${esc(p.categoria)} · ${esc(p.tipo_transaccion)}</div>
        </td>
        <td>
            <div class="small">${esc(p.usuario_nombre)}</div>
            <div class="small text-muted">${esc(p.usuario_email)}</div>
        </td>
        <td><span class="small">${esc(p.categoria)}</span></td>
        <td>${precio}</td>
        <td><span class="badge ${badgeEstado}">${(p.estado_publicacion || '').toUpperCase()}</span></td>
        <td class="small text-muted">${new Date(p.fecha_publicacion).toLocaleDateString('es-ES')}</td>
        <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
                <button class="btn btn-sm btn-outline-success" onclick="accionProducto(${p.id},'activar')" title="Activar">
                    <i class="bi bi-play-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="accionProducto(${p.id},'pausar')" title="Pausar">
                    <i class="bi bi-pause-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="accionProducto(${p.id},'eliminar')" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>`;
    return tr;
}

window.accionProducto = function (id, action) {
    const config = {
        activar:  { titulo: "¿Activar producto?",  mensaje: `El producto #${id} volverá a ser visible.`,             btnTexto: "Activar",  btnClass: "btn-success" },
        pausar:   { titulo: "¿Pausar producto?",   mensaje: `El producto #${id} dejará de ser visible temporalmente.`,btnTexto: "Pausar",   btnClass: "btn-warning" },
        eliminar: { titulo: "¿Eliminar producto?", mensaje: `El producto #${id} se eliminará de forma permanente.`,   btnTexto: "Eliminar", btnClass: "btn-danger"  },
    };
    const cfg = config[action] || { titulo: "¿Confirmar?", mensaje: "", btnTexto: "Confirmar", btnClass: "btn-primary" };

    adminConfirm({
        ...cfg,
        onConfirm: async () => {
            try {
                const res  = await fetch(`${BASE}/api/admin_update_product.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id, action })
                });
                const json = await res.json();
                if (json.success) {
                    cargarProductos();
                    cargarEstadisticas();
                    adminToast(`Producto #${id}: acción "${action}" completada.`, "success");
                } else {
                    adminToast("Error: " + json.error);
                }
            } catch {
                adminToast("Error de conexión.");
            }
        }
    });
};

// ── REPORTES ──────────────────────────────────────────────────
async function cargarReportes() {
    const tbody     = document.getElementById("tabla-reportes");
    const paginador = document.getElementById("paginacion-reportes");

    tbody.innerHTML = spinnerRow(8);

    try {
        const params = new URLSearchParams({
            page:   state.reportes.page,
            limit:  state.reportes.limit,
            estado: state.reportes.estado
        });
        const res  = await fetch(`${BASE}/api/admin_get_reports.php?${params}`);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = errorRow(8, json.error || "Error cargando reportes");
            paginador.innerHTML = "";
            return;
        }

        if (!json.data.length) {
            tbody.innerHTML = emptyRow(8, "No se encontraron reportes.");
            paginador.innerHTML = "";
            return;
        }

        tbody.innerHTML = "";
        json.data.forEach(r => tbody.appendChild(crearFilaReporte(r)));
        renderPaginacion(paginador, json.total, state.reportes, cargarReportes);

    } catch (err) {
        console.error(err);
        tbody.innerHTML = errorRow(8, "Error de conexión.");
    }
}

function crearFilaReporte(r) {
    const tr = document.createElement("tr");

    const badgeClasses = { pendiente: "badge-pendiente", revisado: "badge-revisado", rechazado: "badge-rechazado" };
    const badgeCls = badgeClasses[r.estado] || "bg-secondary";

    const producto = r.producto_titulo
        ? `<div class="fw-semibold small">${esc(r.producto_titulo)}</div><div class="small text-muted">#${r.producto_id}</div>`
        : `<span class="text-muted small">Producto eliminado</span>`;

    const propietario = r.propietario_nombre
        ? `<div class="small">${esc(r.propietario_nombre)}</div><div class="small text-muted">${esc(r.propietario_email || '')}</div>`
        : `<span class="text-muted small">—</span>`;

    let acciones = "";
    if (r.estado === "pendiente") {
        acciones = `
            <div class="d-flex gap-1 justify-content-center">
                <button class="btn btn-sm btn-outline-primary" onclick="resolverReporte(${r.id},'revisado')" title="Marcar revisado">
                    <i class="bi bi-check-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="resolverReporte(${r.id},'rechazado')" title="Rechazar">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>`;
    } else {
        acciones = `<button class="btn btn-sm btn-outline-warning" onclick="resolverReporte(${r.id},'pendiente')" title="Reabrir">
            <i class="bi bi-arrow-counterclockwise"></i>
        </button>`;
    }

    tr.innerHTML = `
        <td class="fw-bold text-muted">#${r.id}</td>
        <td>${producto}</td>
        <td>
            <div class="small">${esc(r.reportador_nombre)}</div>
            <div class="small text-muted">${esc(r.reportador_email)}</div>
        </td>
        <td>${propietario}</td>
        <td><span class="small" title="${esc(r.motivo || '')}">${truncar(r.motivo || '—', 50)}</span></td>
        <td class="small text-muted">${new Date(r.fecha).toLocaleDateString('es-ES')}</td>
        <td><span class="badge ${badgeCls}">${(r.estado || '').toUpperCase()}</span></td>
        <td class="text-center">${acciones}</td>`;
    return tr;
}

window.resolverReporte = function (id, estado) {
    const config = {
        revisado:  { titulo: "¿Marcar como revisado?", mensaje: "El reporte quedará marcado como revisado.",  btnTexto: "Marcar revisado", btnClass: "btn-primary"   },
        rechazado: { titulo: "¿Rechazar reporte?",     mensaje: "El reporte quedará descartado.",            btnTexto: "Rechazar",        btnClass: "btn-secondary" },
        pendiente: { titulo: "¿Reabrir reporte?",      mensaje: "El reporte volverá al estado pendiente.",   btnTexto: "Reabrir",         btnClass: "btn-warning"   },
    };
    const cfg = config[estado] || { titulo: "¿Confirmar?", mensaje: "", btnTexto: "Confirmar", btnClass: "btn-primary" };

    adminConfirm({
        ...cfg,
        onConfirm: async () => {
            try {
                const res  = await fetch(`${BASE}/api/admin_update_report.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id, estado })
                });
                const json = await res.json();
                if (json.success) {
                    cargarReportes();
                    cargarEstadisticas();
                    adminToast(`Reporte #${id} actualizado.`, "success");
                } else {
                    adminToast("Error: " + json.error);
                }
            } catch {
                adminToast("Error de conexión.");
            }
        }
    });
};

// ── PAGINACIÓN ────────────────────────────────────────────────
function renderPaginacion(container, total, stateObj, reloadFn) {
    const totalPages = Math.ceil(total / stateObj.limit);

    if (totalPages <= 1) {
        container.innerHTML = "";
        return;
    }

    const maxVisible = 5;
    let startPage = Math.max(1, stateObj.page - Math.floor(maxVisible / 2));
    let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);

    let items = `
        <li class="page-item ${stateObj.page <= 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="irPagina(${stateObj.page - 1}, '${stateObj === state.usuarios ? 'usuarios' : stateObj === state.productos ? 'productos' : 'reportes'}')">
                <i class="bi bi-chevron-left"></i>
            </button>
        </li>`;

    for (let i = startPage; i <= endPage; i++) {
        items += `
            <li class="page-item ${i === stateObj.page ? 'active' : ''}">
                <button class="page-link" onclick="irPagina(${i}, '${stateObj === state.usuarios ? 'usuarios' : stateObj === state.productos ? 'productos' : 'reportes'}')">${i}</button>
            </li>`;
    }

    items += `
        <li class="page-item ${stateObj.page >= totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="irPagina(${stateObj.page + 1}, '${stateObj === state.usuarios ? 'usuarios' : stateObj === state.productos ? 'productos' : 'reportes'}')">
                <i class="bi bi-chevron-right"></i>
            </button>
        </li>`;

    container.innerHTML = `<ul class="pagination pagination-sm">${items}</ul>
        <small class="text-muted ms-3 align-self-center">${total} registros · pág. ${stateObj.page}/${totalPages}</small>`;
}

window.irPagina = function (page, seccion) {
    if (seccion === "usuarios") {
        state.usuarios.page = page;
        cargarUsuarios();
    } else if (seccion === "productos") {
        state.productos.page = page;
        cargarProductos();
    } else if (seccion === "reportes") {
        state.reportes.page = page;
        cargarReportes();
    }
};

// ── Modal de confirmación (reemplaza confirm() nativo) ────────
function adminConfirm({ titulo, mensaje, btnTexto = "Confirmar", btnClass = "btn-danger", onConfirm }) {
    const existente = document.getElementById("admin-confirm-modal");
    if (existente) existente.remove();

    const div = document.createElement("div");
    div.id        = "admin-confirm-modal";
    div.className = "modal fade";
    div.setAttribute("tabindex", "-1");
    div.setAttribute("aria-modal", "true");
    div.setAttribute("role", "dialog");
    div.innerHTML = `
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">${esc(titulo)}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-1">${esc(mensaje)}</div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm ${btnClass}" id="admin-confirm-ok">${esc(btnTexto)}</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(div);

    const modal = new bootstrap.Modal(div);
    modal.show();

    div.querySelector("#admin-confirm-ok").addEventListener("click", () => {
        modal.hide();
        onConfirm();
    });
    div.addEventListener("hidden.bs.modal", () => div.remove());
}

function adminToast(msg, tipo = "error") {
    if (typeof mostrarToast === "function") {
        mostrarToast(msg, tipo);
    } else {
        console.error(msg);
    }
}

// ── Utilidades ────────────────────────────────────────────────
function esc(str) {
    if (!str) return "";
    return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function truncar(str, max) {
    return str.length > max ? str.substring(0, max) + "…" : str;
}

function spinnerRow(cols) {
    return `<tr><td colspan="${cols}" class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>Cargando...
    </td></tr>`;
}

function errorRow(cols, msg) {
    return `<tr><td colspan="${cols}" class="text-center text-danger py-4">${esc(msg)}</td></tr>`;
}

function emptyRow(cols, msg) {
    return `<tr><td colspan="${cols}" class="text-center py-4 text-muted">${esc(msg)}</td></tr>`;
}
