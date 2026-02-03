/**
 * Lista de archivos seleccionados por el usuario.
 * Se mantiene sincronizada con el orden visual del grid.
 * @type {File[]}
 */
let selectedFiles = [];

/**
 * Input de tipo file donde el usuario selecciona imágenes.
 * @type {HTMLInputElement}
 */
const input = document.getElementById("imagenesInput");

/**
 * Contenedor donde se renderizan las miniaturas de las imágenes.
 * @type {HTMLElement}
 */
const grid = document.getElementById("previewGrid");

/**
 * Input oculto donde se guarda el orden de las imágenes.
 * @type {HTMLInputElement}
 */
const ordenInput = document.getElementById("ordenImagenes");


/* ============================================================
   EVENTO: SELECCIÓN DE NUEVAS IMÁGENES
============================================================ */

/**
 * Evento que se ejecuta cuando el usuario selecciona nuevas imágenes.
 * Agrega los archivos al array `selectedFiles`, renderiza la vista previa
 * y reconstruye la FileList del input.
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
 * Renderiza la cuadrícula de imágenes seleccionadas.
 * Utiliza FileReader para generar vistas previas.
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
 * Actualiza el input oculto con el orden actual de las imágenes
 * según el DOM del grid.
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
 * Mantiene sincronizado el array `selectedFiles` con el orden visual.
 */
new Sortable(grid, {
    animation: 150,
    ghostClass: "bg-light",

    /**
     * Evento que se ejecuta cuando el usuario reordena las imágenes.
     */
    onSort: function() {

        // 1. Leer el DOM en el nuevo orden
        const items = Array.from(grid.children);

        // 2. Crear un nuevo array de archivos según el orden visual
        const newOrder = items.map(col => selectedFiles[col.dataset.index]);

        // 3. Reemplazar el array original
        selectedFiles = newOrder;

        // 4. Actualizar los índices visibles
        items.forEach((col, idx) => col.dataset.index = idx);

        // 5. Actualizar input hidden
        updateOrder();

        // 6. Reconstruir FileList para el form
        rebuildFileList();
    }
});


/* ============================================================
   RECONSTRUIR FILELIST
============================================================ */

/**
 * Reconstruye el objeto FileList del input file usando DataTransfer.
 * Esto permite enviar las imágenes en el orden correcto al servidor.
 */
function rebuildFileList() {
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}