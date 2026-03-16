/**
 * Variables globales para la paginación y búsqueda
 */
let currentPage = 1;
const limit = 10;
let searchQuery = "";

/**
 * Evento principal al cargar el DOM
 */
document.addEventListener("DOMContentLoaded", () => {
    cargarUsuarios();

    // Evento para el buscador
    const btnSearch = document.getElementById("btn-search");
    const inputSearch = document.getElementById("search-user");

    btnSearch?.addEventListener("click", () => {
        searchQuery = inputSearch.value.trim();
        currentPage = 1; // Volver a la primera página al buscar
        cargarUsuarios();
    });

    // Permitir buscar al presionar Enter
    inputSearch?.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
            searchQuery = inputSearch.value.trim();
            currentPage = 1;
            cargarUsuarios();
        }
    });
});

/**
 * Obtiene los usuarios de la API y los renderiza en la tabla
 */
async function cargarUsuarios() {
    const tbody = document.getElementById("tabla-usuarios");
    const paginacion = document.getElementById("paginacion-admin");

    // Mostrar estado de carga
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                Cargando usuarios...
            </td>
        </tr>`;

    try {
        // Construir la URL con los parámetros
        const params = new URLSearchParams({
            page: currentPage,
            limit: limit,
            q: searchQuery
        });

        // Asegúrate de que la ruta coincida con el nombre de tu carpeta raíz si es necesario
        const res = await fetch(`/MercApp/api/admin_get_users.php?${params.toString()}`);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${json.error || "Error cargando usuarios"}</td></tr>`;
            paginacion.innerHTML = "";
            return;
        }

        const usuarios = json.data;
        const total = json.total;

        if (usuarios.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron usuarios.</td></tr>`;
            paginacion.innerHTML = "";
            return;
        }

        // Limpiar tabla e inyectar usuarios
        tbody.innerHTML = "";
        usuarios.forEach(u => {
            const tr = document.createElement("tr");

            // Colores para los estados
            let badgeEstado = "bg-success";
            if (u.estado === "suspendido") badgeEstado = "bg-warning text-dark";
            if (u.estado === "eliminado") badgeEstado = "bg-danger";

            // Colores para los roles
            const badgeRol = u.rol === "admin" ? "bg-primary" : "bg-secondary";

            // Lógica de seguridad: Ocultar botones si el usuario es administrador
            let botonesAccion = "";
            if (u.rol === "admin") {
                botonesAccion = `<span class="text-muted small fw-bold"><i class="bi bi-shield-check text-primary"></i> Protegido</span>`;
            } else {
                botonesAccion = `
                    <button class="btn btn-sm btn-outline-warning me-1" onclick="cambiarEstado(${u.id}, 'suspendido')" title="Suspender">
                        <i class="bi bi-pause-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success me-1" onclick="cambiarEstado(${u.id}, 'activo')" title="Activar">
                        <i class="bi bi-play-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="cambiarEstado(${u.id}, 'eliminado')" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            }

            tr.innerHTML = `
                <td class="fw-bold text-muted">#${u.id}</td>
                <td>
                    <div class="fw-semibold">${u.nombre} ${u.apellidos || ''}</div>
                    <div class="small text-muted">${u.telefono || 'Sin teléfono'}</div>
                </td>
                <td>${u.email}</td>
                <td>${new Date(u.fecha_registro).toLocaleDateString()}</td>
                <td><span class="badge ${badgeRol}">${u.rol.toUpperCase()}</span></td>
                <td><span class="badge ${badgeEstado}">${u.estado.toUpperCase()}</span></td>
                <td class="text-center">
                    ${botonesAccion}
                </td>
            `;
            tbody.appendChild(tr);
        });

        renderizarPaginacion(total);

    } catch (err) {
        console.error("Error al cargar usuarios:", err);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error de conexión al servidor.</td></tr>`;
    }
}

/**
 * Dibuja los controles de paginación
 */
function renderizarPaginacion(total) {
    const paginacion = document.getElementById("paginacion-admin");
    const totalPages = Math.ceil(total / limit);

    if (totalPages <= 1) {
        paginacion.innerHTML = "";
        return;
    }

    paginacion.innerHTML = `
        <ul class="pagination">
            <li class="page-item ${currentPage <= 1 ? "disabled" : ""}">
                <button class="page-link" onclick="cambiarPagina(${currentPage - 1})">Anterior</button>
            </li>
            <li class="page-item disabled">
                <span class="page-link">Página ${currentPage} de ${totalPages}</span>
            </li>
            <li class="page-item ${currentPage >= totalPages ? "disabled" : ""}">
                <button class="page-link" onclick="cambiarPagina(${currentPage + 1})">Siguiente</button>
            </li>
        </ul>
    `;
}

/**
 * Cambia la página actual y recarga la tabla
 */
window.cambiarPagina = function (newPage) {
    currentPage = newPage;
    cargarUsuarios();
};

/**
 * Envía la petición a la API para cambiar el estado de un usuario
 */
window.cambiarEstado = async function (id, nuevoEstado) {
    if (confirm(`¿Estás seguro de que deseas marcar al usuario #${id} como ${nuevoEstado}?`)) {
        try {
            // Ajusta la ruta a tu carpeta raíz si es diferente a /MercApp/
            const res = await fetch("/MercApp/api/admin_update_status.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: id,
                    estado: nuevoEstado
                })
            });

            const json = await res.json();

            if (json.success) {
                // Si todo sale bien, recargamos la tabla para ver la insignia cambiar de color
                cargarUsuarios();
            } else {
                // Si la API lo rechaza (ej. intentan banear a un admin saltándose el HTML)
                alert("Error: " + json.error);
            }
        } catch (err) {
            console.error(err);
            alert("Error de conexión al servidor.");
        }
    }
};