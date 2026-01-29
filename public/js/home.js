/* ============================================================
   VARIABLES GLOBALES
============================================================ */
let offset = 0;
const limit = 12;
let loading = false;
let noMore = false;

/* Filtros del buscador */
let searchQuery = "";
let searchCategoria = "";
let searchEstado = "";
let searchTransaccion = "";
let searchOrden = "fecha_desc";

/* ============================================================
   SKELETON LOADER
============================================================ */
function showSkeleton(count = 8) {
    const container = document.getElementById("skeleton-loader");
    container.innerHTML = "";

    for (let i = 0; i < count; i++) {
        const col = document.createElement("div");
        col.classList.add("col-6", "col-md-4", "col-lg-3");

        col.innerHTML = `
            <div class="card h-100 shadow-sm border-0">
                <div class="placeholder-glow">
                    <div class="card-img-top placeholder" style="height:180px;"></div>
                </div>

                <div class="card-body">
                    <div class="card-title placeholder-glow">
                        <span class="placeholder col-8"></span>
                    </div>

                    <p class="placeholder-glow">
                        <span class="placeholder col-4"></span>
                    </p>

                    <div class="placeholder-glow mt-auto">
                        <span class="placeholder col-12 btn btn-outline-primary disabled"></span>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(col);
    }

    container.style.display = "flex";
}

function hideSkeleton() {
    document.getElementById("skeleton-loader").style.display = "none";
}

/* ============================================================
   CARGA DE FILTROS DESDE LA BD
============================================================ */
async function cargarFiltros() {
    try {
        const res = await fetch("/MercApp/api/get_filters.php");
        const json = await res.json();

        if (!json.success) return;

        // Categorías
        const catSelect = document.getElementById("filtro-categoria");
        json.categorias.forEach(cat => {
            const opt = document.createElement("option");
            opt.value = cat.id;
            opt.textContent = cat.nombre;
            catSelect.appendChild(opt);
        });

        // Estado del producto
        const estadoSelect = document.getElementById("filtro-estado");
        json.estado_producto.forEach(est => {
            const opt = document.createElement("option");
            opt.value = est.id;
            opt.textContent = est.nombre;
            estadoSelect.appendChild(opt);
        });

        // Tipo de transacción
        const transSelect = document.getElementById("filtro-transaccion");
        json.tipos_transaccion.forEach(tipo => {
            const opt = document.createElement("option");
            opt.value = tipo;
            opt.textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);
            transSelect.appendChild(opt);
        });

    } catch (err) {
        console.error("Error cargando filtros:", err);
    }
}

/* ============================================================
   CARGA DE PRODUCTOS
============================================================ */
async function loadMoreProducts() {
    if (loading || noMore) return;

    loading = true;
    showSkeleton();

    try {
        let endpoint = "/MercApp/api/getProductsPaginated.php";

        const params = new URLSearchParams({
            limit,
            offset
        });

        if (
            searchQuery ||
            searchCategoria ||
            searchEstado ||
            searchTransaccion ||
            searchOrden !== "fecha_desc"
        ) {
            endpoint = "/MercApp/api/search_products.php";

            params.set("q", searchQuery);
            params.set("categoria", searchCategoria);
            params.set("estado_producto", searchEstado);
            params.set("tipo_transaccion", searchTransaccion);
            params.set("orden", searchOrden);
        }

        const res = await fetch(`${endpoint}?${params.toString()}`);
        const json = await res.json();

        if (!json.success) throw new Error("Error en API");

        const products = json.data;

        renderProducts(products);

        if (products.length < limit) {
            noMore = true;
            observer.disconnect();
        }

        offset += limit;

    } catch (err) {
        console.error(err);
        document.getElementById("error").style.display = "block";
    } finally {
        loading = false;
        hideSkeleton();
    }
}

/* ============================================================
   RENDER DE PRODUCTOS
============================================================ */
function renderProducts(products) {
    const container = document.getElementById("product-list");

    products.forEach(p => {
        const img = p.imagenes?.length
            ? `/MercApp/${p.imagenes[0].url}`
            : "/MercApp/uploads/products/default.jpg";

        const col = document.createElement("div");
        col.classList.add("col-6", "col-md-4", "col-lg-3", "mb-4");

        col.innerHTML = `
            <div class="card h-100 shadow-sm border-0">
                <img src="${img}" class="card-img-top" alt="${p.titulo}" style="height: 180px; object-fit: cover;">

                <div class="card-body d-flex flex-column">
                    <h2 class="card-title text-truncate">${p.titulo}</h2>

                    <p class="card-text fw-bold text-primary mb-3">
                        ${p.precio} €
                    </p>

                    <a href="detalle_producto.php?id=${p.id}" 
                       class="btn btn-outline-primary mt-auto w-100">
                        Ver más
                    </a>
                </div>
            </div>
        `;

        container.appendChild(col);
    });
}

/* ============================================================
   RESET DE BÚSQUEDA
============================================================ */
function resetAndSearch() {
    offset = 0;
    noMore = false;

    document.getElementById("product-list").innerHTML = "";
    observer.observe(sentinel);

    loadMoreProducts();
}

/* ============================================================
   EVENTOS
============================================================ */
document.addEventListener("DOMContentLoaded", async () => {

    await cargarFiltros();

    const params = new URLSearchParams(window.location.search);
    const q = params.get("q");

    if (q) {
        searchQuery = q;
        const navbarInput = document.getElementById("navbar-search");
        if (navbarInput) navbarInput.value = q;
    }

    const navbarInput = document.getElementById("navbar-search");
    navbarInput?.addEventListener("input", e => {
        searchQuery = e.target.value.trim();
        resetAndSearch();
    });

    document.getElementById("filtro-categoria")?.addEventListener("change", e => {
        searchCategoria = e.target.value;
        resetAndSearch();
    });

    document.getElementById("filtro-estado")?.addEventListener("change", e => {
        searchEstado = e.target.value;
        resetAndSearch();
    });

    document.getElementById("filtro-transaccion")?.addEventListener("change", e => {
        searchTransaccion = e.target.value;
        resetAndSearch();
    });

    document.getElementById("filtro-orden")?.addEventListener("change", e => {
        searchOrden = e.target.value;
        resetAndSearch();
    });

    loadMoreProducts();
});

/* ============================================================
   INTERSECTION OBSERVER
============================================================ */
const sentinel = document.getElementById("sentinel");

const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) {
        loadMoreProducts();
    }
});

observer.observe(sentinel);
