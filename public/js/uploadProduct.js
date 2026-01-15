
let selectedFiles = [];

const input = document.getElementById("imagenesInput");
const grid = document.getElementById("previewGrid");
const ordenInput = document.getElementById("ordenImagenes");

input.addEventListener("change", function(e) {
    const files = Array.from(e.target.files);

    files.forEach(file => selectedFiles.push(file));

    renderGrid();
    rebuildFileList();
});

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

function removeImage(index) {
    selectedFiles.splice(index, 1);
    renderGrid();
    rebuildFileList();
}

function updateOrder() {
    const items = Array.from(grid.children);
    const order = items.map(col => col.dataset.index);
    ordenInput.value = order.join(",");
}

new Sortable(grid, {
    animation: 150,
    ghostClass: "bg-light",
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

function rebuildFileList() {
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

