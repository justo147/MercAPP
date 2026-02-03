/**
 * Lista de nuevas imágenes seleccionadas por el usuario.
 * @type {File[]}
 */
let selectedFiles = [];

/**
 * Lista de imágenes ya existentes cargadas desde el servidor.
 * @type {{url: string, orden: number}[]}
 */
let existingImages = [];

/**
 * Input de tipo file donde se seleccionan las imágenes.
 * @type {HTMLInputElement}
 */
const input = document.getElementById("imagenesInput");

/**
 * Contenedor donde se renderizan las miniaturas de las imágenes seleccionadas.
 * @type {HTMLElement}
 */
const grid = document.getElementById("previewGrid");

/**
 * Input oculto donde se guarda el orden de las imágenes.
 * @type {HTMLInputElement}
 */
const ordenInput = document.getElementById("ordenImagenes");


/* ============================================================
   CARGA DE IMÁGENES EXISTENTES
============================================================ */

/**
 * Carga las imágenes actuales desde los atributos data del DOM
 * y las almacena en `existingImages`.
 */
document.querySelectorAll(".img-actual").forEach(img => {
    existingImages.push({
        url: img.dataset.url,
        orden: parseInt(img.dataset.orden)
    });
});


/* ============================================================
   MANEJO DE NUEVAS IMÁGENES
============================================================ */

/**
 * Evento que se ejecuta cuando el usuario selecciona nuevas imágenes.
 * Agrega los archivos seleccionados a `selectedFiles`, renderiza la vista previa
 * y reconstruye la lista interna de archivos.
 */
input.addEventListener("change", function(e) {
    const files = Array.from(e.target.files);
    files.forEach(file => selectedFiles.push(file));

    renderGrid();
    rebuildFileList();
});


/* ============================================================
   RENDERIZADO DE MINIATURAS
============================================================ */

/**
 * Renderiza la cuadrícula de imágenes seleccionadas por el usuario.
 * Utiliza FileReader para mostrar una vista previa de cada imagen.
 */
function renderGrid() {
    grid.innerHTML = "";

    selectedFiles.forEach((file, index) => {
        const col = document.createElement("div");
        col.className = "col-4";
        col.dataset.index = index;

        const reader = new FileReader();

        reader.onload = function(event) {
            col.innerHTML = `
                <div class="position-relative border rounded p-1">
                    <img src="${event.target.result}" class="img-fluid rounded">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1"
                        onclick="removeImage(${index})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `;
        };

        reader.readAsDataURL(file);
        grid.appendChild(col);
    });

    updateOrder();
}


/* ============================================================
   ELIMINAR IMÁGENES
============================================================ */

/**
 * Elimina una imagen seleccionada según su índice.
 * @param {number} index - Índice de la imagen a eliminar.
 */
function removeImage(index) {
    selectedFiles.splice(index, 1);
    renderGrid();
    rebuildFileList();
}


/* ============================================================
   ACTUALIZAR ORDEN
============================================================ */

/**
 * Actualiza el orden de las imágenes en el input oculto `ordenImagenes`.
 */
function updateOrder() {
    const items = Array.from(grid.children);
    const order = items.map(col => col.dataset.index);
    ordenInput.value = order.join(",");
}


/* ============================================================
   SORTABLE.JS PARA REORDENAR IMÁGENES
============================================================ */

/**
 * Inicializa SortableJS para permitir arrastrar y reordenar imágenes.
 * Actualiza `selectedFiles` y el input oculto con el nuevo orden.
 */
new Sortable(grid, {
    animation: 150,
    ghostClass: "bg-light",

    /**
     * Evento que se ejecuta cuando se reordena la cuadrícula.
     */
    onSort: function() {
        const items = Array.from(grid.children);
        const newOrder = items.map(col => selectedFiles[col.dataset.index]);

        selectedFiles = newOrder;

        items.forEach((col, idx) => col.dataset.index = idx);

        updateOrder();
        rebuildFileList();
    }
});


/* ============================================================
   RECONSTRUIR LISTA DE ARCHIVOS
============================================================ */

/**
 * Reconstruye el objeto FileList del input file usando DataTransfer,
 * para que el formulario envíe las imágenes en el orden correcto.
 */
function rebuildFileList() {
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}
