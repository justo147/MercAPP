let offset = 0;
const limit = 12;
let loading = false;
let noMore = false;

/* -----------------------------------
   SKELETON LOADER (Bootstrap)
----------------------------------- */
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
                    <h5 class="card-title placeholder-glow">
                        <span class="placeholder col-8"></span>
                    </h5>

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

/* -----------------------------------
   CARGA DE PRODUCTOS
----------------------------------- */
async function loadMoreProducts() {
    if (loading || noMore) return;

    loading = true;
    showSkeleton(); // Mostrar skeleton mientras carga

    try {
        //await new Promise(r => setTimeout(r, 1500)); // <-- RETRASO ARTIFICIAL
        const res = await fetch(`/MercApp/api/getProductsPaginated.php?limit=${limit}&offset=${offset}`);
        const json = await res.json();

        if (!json.success) throw new Error("Error en API");

        renderProducts(json.data);

        if (json.data.length < limit) {
            noMore = true;
            observer.disconnect(); // Detiene el scroll infinito
        }

        offset += limit;

    } catch (err) {
        console.error(err);
        document.getElementById("error").style.display = "block";
    } finally {
        loading = false;
        hideSkeleton(); // Ocultar skeleton
    }
}

/* -----------------------------------
   RENDER DE PRODUCTOS
----------------------------------- */
function renderProducts(products) {
    const container = document.getElementById("product-list");

    products.forEach(p => {
        const img = p.imagenes.length > 0
            ? p.imagenes[0].url
            : "../img/default.jpg";

        const col = document.createElement("div");
        col.classList.add("col-6", "col-md-4", "col-lg-3", "mb-4");

        col.innerHTML = `
            <div class="card h-100 shadow-sm border-0">
                <img src="${img}" class="card-img-top" alt="${p.titulo}" style="height: 180px; object-fit: cover;">

                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-truncate">${p.titulo}</h5>

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

/* -----------------------------------
   INTERSECTION OBSERVER
----------------------------------- */
const sentinel = document.getElementById("sentinel");

const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) {
        loadMoreProducts();
    }
});

observer.observe(sentinel);

/* -----------------------------------
   CARGA INICIAL
----------------------------------- */
loadMoreProducts();
