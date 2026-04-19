/* ============================================================
   VARIABLES GLOBALES
============================================================ */

/**
 * Offset actual para la paginación de productos.
 * @type {number}
 */
let offset = 0;

/**
 * Número máximo de productos cargados por petición.
 * @type {number}
 */
const limit = 12;

/**
 * Indica si actualmente se está realizando una carga de productos.
 * @type {boolean}
 */
let loading = false;

/**
 * Indica si ya no hay más productos para cargar.
 * @type {boolean}
 */
let noMore = false;

/* Filtros del buscador */

/**
 * Texto de búsqueda introducido por el usuario.
 * @type {string}
 */
let searchQuery = "";

/**
 * ID de la categoría seleccionada.
 * @type {string}
 */
let searchCategoria = "";

/**
 * ID del estado del producto seleccionado.
 * @type {string}
 */
let searchEstado = "";

/**
 * Tipo de transacción seleccionada.
 * @type {string}
 */
let searchTransaccion = "";

/**
 * Orden seleccionado para la búsqueda.
 * @type {string}
 */
let searchOrden = "fecha_desc";

/** Coordenadas para filtro de proximidad */
let searchLat = null;
let searchLon = null;
let searchDistancia = 10;

/* ============================================================
   SKELETON LOADER
============================================================ */

/**
 * Muestra un skeleton loader con tarjetas simuladas mientras se cargan productos.
 * @param {number} [count=8] - Número de tarjetas skeleton a mostrar.
 */
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

/**
 * Oculta el skeleton loader.
 */
function hideSkeleton() {
    document.getElementById("skeleton-loader").style.display = "none";
}

/* ============================================================
   CARGA DE FILTROS DESDE LA BD
============================================================ */

/**
 * Carga los filtros disponibles desde la API y los inserta en los select correspondientes.
 * @async
 * @returns {Promise<void>}
 */
async function cargarFiltros() {
    try {
        const res = await fetch(`${BASE}/api/get_filters.php`);
        const json = await res.json();

        if (!json.success) return;

        // Categorías (con conteo de productos activos)
        const catSelect = document.getElementById("filtro-categoria");
        json.categorias.forEach(cat => {
            const opt = document.createElement("option");
            opt.value = cat.id;
            opt.textContent = cat.total > 0
                ? `${cat.nombre} (${cat.total})`
                : cat.nombre;
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

/**
 * Carga más productos desde la API aplicando paginación y filtros.
 * Gestiona el skeleton loader y actualiza el offset.
 * @async
 * @returns {Promise<void>}
 */
async function loadMoreProducts() {
    if (loading || noMore) return;

    loading = true;

    let skeletonTimeout = null;
    const isFirstLoad = offset === 0;

    if (isFirstLoad) {
        skeletonTimeout = setTimeout(() => {
            showSkeleton();
        }, 150);
    }

    try {
        let endpoint = `${BASE}/api/getProductsPaginated.php`;

        const params = new URLSearchParams({
            limit,
            offset
        });

        const usingFilters =
            searchQuery ||
            searchCategoria ||
            searchEstado ||
            searchTransaccion ||
            searchOrden !== "fecha_desc" ||
            (searchOrden === "distancia" && searchLat && searchLon);

        if (usingFilters) {
            endpoint = `${BASE}/api/search_products.php`;

            params.set("q", searchQuery);
            params.set("categoria", searchCategoria);
            params.set("estado_producto", searchEstado);
            params.set("tipo_transaccion", searchTransaccion);
            params.set("orden", searchOrden);

            if (searchLat && searchLon && searchOrden === "distancia") {
                params.set("lat", searchLat);
                params.set("lon", searchLon);
                params.set("distancia_km", searchDistancia);
            }
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

        if (skeletonTimeout) clearTimeout(skeletonTimeout);
        if (isFirstLoad) hideSkeleton();
    }
}

/* ============================================================
   RENDER DE PRODUCTOS
============================================================ */

/**
 * Renderiza una lista de productos en el contenedor principal.
 * @param {Array<Object>} products - Lista de productos obtenidos desde la API.
 */
function renderProducts(products) {
    const container = document.getElementById("product-list");

    products.forEach(p => {
        const DEFAULT_IMG = `${BASE}/public/img/default.jpg`;
        const img = p.imagenes?.length
            ? `${BASE}/${p.imagenes[0].url}`
            : DEFAULT_IMG;

        const col = document.createElement("div");
        col.classList.add("col-6", "col-md-4", "col-lg-3", "mb-4", "sinFondo", "fade-in");

        col.innerHTML = `
            <div class="card h-100 shadow-sm border">
                <img src="${img}" class="card-img-top" alt="Imagen de ${p.titulo}"
                     style="height: 180px; object-fit: cover;"
                     onerror="this.onerror=null;this.src='${DEFAULT_IMG}'">

                <div class="card-body d-flex flex-column">
                    <h2 class="card-title text-truncate small" title="${p.titulo}">${p.titulo}</h2>

                    <p class="card-text fw-bold text-primary mb-3">
                        ${p.precio} €
                    </p>

                    <a href="detail_product.php?id=${p.id}" 
                       class="btn btn-outline-primary mt-auto w-100">
                        Ver más
                    </a>
                </div>
            </div>
        `;

        container.appendChild(col);

        //Forzamos reflow para que la animación SIEMPRE se dispare
        void col.offsetWidth;
        col.classList.add("show");
    });
}

/* ============================================================
   RESET DE BÚSQUEDA
============================================================ */

/**
 * Sincroniza el estado actual de los filtros con la URL usando history.pushState,
 * para que el usuario pueda copiar/compartir la búsqueda y volver con el botón atrás.
 */
function syncURL() {
    const params = new URLSearchParams();
    if (searchQuery)                          params.set("q",      searchQuery);
    if (searchCategoria)                      params.set("cat",    searchCategoria);
    if (searchEstado)                         params.set("estado", searchEstado);
    if (searchTransaccion)                    params.set("tipo",   searchTransaccion);
    if (searchOrden && searchOrden !== "fecha_desc") params.set("orden", searchOrden);

    const newUrl = params.toString()
        ? `${window.location.pathname}?${params}`
        : window.location.pathname;

    history.pushState({ q: searchQuery, cat: searchCategoria }, "", newUrl);
}

/**
 * Reinicia los parámetros de búsqueda y vuelve a cargar los productos desde cero.
 */
function resetAndSearch() {
    offset = 0;
    noMore = false;

    document.getElementById("product-list").innerHTML = "";
    observer.observe(sentinel);

    syncURL();
    loadMoreProducts();
}

/* ============================================================
   EVENTOS
============================================================ */

/**
 * Inicializa los eventos de la página, carga filtros y gestiona la búsqueda.
 */
document.addEventListener("DOMContentLoaded", async () => {

    await cargarFiltros();

    // Restaurar estado de filtros desde la URL
    const params = new URLSearchParams(window.location.search);

    if (params.get("q")) {
        searchQuery = params.get("q");
        const navbarInput = document.getElementById("navbar-search");
        if (navbarInput) navbarInput.value = searchQuery;
    }
    if (params.get("cat")) {
        searchCategoria = params.get("cat");
        const sel = document.getElementById("filtro-categoria");
        if (sel) sel.value = searchCategoria;
    }
    if (params.get("estado")) {
        searchEstado = params.get("estado");
        const sel = document.getElementById("filtro-estado");
        if (sel) sel.value = searchEstado;
    }
    if (params.get("tipo")) {
        searchTransaccion = params.get("tipo");
        const sel = document.getElementById("filtro-transaccion");
        if (sel) sel.value = searchTransaccion;
    }
    if (params.get("orden")) {
        searchOrden = params.get("orden");
        const sel = document.getElementById("filtro-orden");
        if (sel) sel.value = searchOrden;
    }

    // Restaurar estado al navegar con botón atrás
    window.addEventListener("popstate", () => {
        const p = new URLSearchParams(window.location.search);
        searchQuery       = p.get("q")      || "";
        searchCategoria   = p.get("cat")    || "";
        searchEstado      = p.get("estado") || "";
        searchTransaccion = p.get("tipo")   || "";
        searchOrden       = p.get("orden")  || "fecha_desc";

        const nav = document.getElementById("navbar-search");
        if (nav) nav.value = searchQuery;
        const selCat   = document.getElementById("filtro-categoria");   if (selCat)   selCat.value   = searchCategoria;
        const selEst   = document.getElementById("filtro-estado");      if (selEst)   selEst.value   = searchEstado;
        const selTipo  = document.getElementById("filtro-transaccion"); if (selTipo)  selTipo.value  = searchTransaccion;
        const selOrden = document.getElementById("filtro-orden");       if (selOrden) selOrden.value = searchOrden;

        offset = 0; noMore = false;
        document.getElementById("product-list").innerHTML = "";
        observer.observe(sentinel);
        loadMoreProducts();
    });

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
        const proximidadWrap = document.getElementById("filtro-proximidad-wrap");
        if (proximidadWrap) {
            proximidadWrap.style.display = (searchOrden === "distancia") ? "block" : "none";
        }
        resetAndSearch();
    });

    document.getElementById("filtro-distancia")?.addEventListener("change", e => {
        searchDistancia = parseInt(e.target.value);
        resetAndSearch();
    });

    document.getElementById("btn-geolocalizar")?.addEventListener("click", () => {
        if (!navigator.geolocation) {
            alert("Tu navegador no soporta geolocalización.");
            return;
        }
        navigator.geolocation.getCurrentPosition(
            pos => {
                searchLat = pos.coords.latitude;
                searchLon = pos.coords.longitude;
                const label = document.getElementById("proximidad-coords-label");
                if (label) label.textContent = `Lat ${searchLat.toFixed(4)}, Lon ${searchLon.toFixed(4)}`;
                resetAndSearch();
            },
            () => alert("No se pudo obtener tu ubicación. Asegúrate de dar permiso al navegador.")
        );
    });

    loadMoreProducts();
});

/* ============================================================
   INTERSECTION OBSERVER
============================================================ */

/**
 * Elemento utilizado como referencia para detectar el final del scroll.
 * @type {HTMLElement}
 */
const sentinel = document.getElementById("sentinel");

/**
 * Observador que detecta cuando el usuario llega al final de la lista para cargar más productos.
 * @type {IntersectionObserver}
 */
const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) {
        loadMoreProducts();
    }
});

observer.observe(sentinel);
