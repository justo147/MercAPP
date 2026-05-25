/** Comprime un File de imagen usando Canvas y devuelve una Promise<File> */
function compressImage(file, maxWidth = 1400, quality = 0.82) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                let w = img.width;
                let h = img.height;
                if (w > maxWidth) {
                    h = Math.round(h * maxWidth / w);
                    w = maxWidth;
                }
                const canvas = document.createElement('canvas');
                canvas.width  = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob((blob) => {
                    const compressed = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
                    resolve(compressed);
                }, 'image/jpeg', quality);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

let selectedFiles = [];

const input      = document.getElementById("imagenesInput");
const grid       = document.getElementById("previewGrid");
const ordenInput = document.getElementById("ordenImagenes");

/* ── SELECCIÓN DE IMÁGENES ─────────────────────────────────── */
input.addEventListener("change", async function (e) {
    const raw = Array.from(e.target.files);
    const compressed = await Promise.all(raw.map(f => compressImage(f)));
    compressed.forEach(f => selectedFiles.push(f));
    renderGrid();
    rebuildFileList();
});

/* ── RENDERIZADO DE MINIATURAS ─────────────────────────────── */
function renderGrid() {
    grid.innerHTML = "";
    selectedFiles.forEach((file, index) => {
        const col = document.createElement("div");
        col.className = "col-4";
        col.dataset.index = index;

        const url = URL.createObjectURL(file);
        col.innerHTML = `
            <div class="position-relative border rounded p-1">
                <img src="${url}" class="img-fluid rounded" style="aspect-ratio:1;object-fit:cover;">
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1"
                    onclick="removeImage(${index})">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>`;
        grid.appendChild(col);
    });
    updateOrder();
}

/* ── ELIMINAR IMAGEN ───────────────────────────────────────── */
function removeImage(index) {
    selectedFiles.splice(index, 1);
    renderGrid();
    rebuildFileList();
}

/* ── ORDEN ─────────────────────────────────────────────────── */
function updateOrder() {
    ordenInput.value = Array.from(grid.children).map(c => c.dataset.index).join(",");
}

/* ── SORTABLE ──────────────────────────────────────────────── */
new Sortable(grid, {
    animation: 150,
    ghostClass: "bg-light",
    onSort() {
        const items = Array.from(grid.children);
        selectedFiles = items.map(c => selectedFiles[c.dataset.index]);
        items.forEach((c, i) => c.dataset.index = i);
        updateOrder();
        rebuildFileList();
    }
});

/* ── RECONSTRUIR FILELIST ──────────────────────────────────── */
function rebuildFileList() {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    input.files = dt.files;
}
