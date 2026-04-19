/**
 * address_autocomplete.js
 * Widget reutilizable de autocompletado de direcciones usando Nominatim (OpenStreetMap).
 *
 * Uso:
 *   initAddressAutocomplete('id-del-input', '/MercApp/api/normalize_address.php');
 *
 * El input seleccionado muestra sugerencias mientras el usuario escribe.
 * Al elegir una sugerencia, el input se rellena con la dirección normalizada.
 */

function initAddressAutocomplete(inputId, apiUrl) {
    const input = document.getElementById(inputId);
    if (!input) return;

    // Contenedor de sugerencias
    const dropdown = document.createElement('ul');
    dropdown.className = 'address-suggestions list-group position-absolute w-100 shadow';
    dropdown.style.cssText = 'z-index:1055;max-height:220px;overflow-y:auto;display:none;';

    // Envolver input en un div relativo para posicionar el dropdown
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    wrapper.appendChild(dropdown);

    let debounceTimer = null;
    let lastQuery = '';

    input.addEventListener('input', () => {
        const q = input.value.trim();
        if (q === lastQuery) return;
        lastQuery = q;

        clearTimeout(debounceTimer);
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';

        if (q.length < 3) return;

        // Debounce 400ms para no saturar Nominatim (1 req/seg)
        debounceTimer = setTimeout(() => fetchSuggestions(q), 400);
    });

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    function fetchSuggestions(query) {
        const url = apiUrl + '?q=' + encodeURIComponent(query);

        fetch(url)
            .then(r => r.json())
            .then(results => renderSuggestions(results))
            .catch(() => {
                // Si falla la API, no mostramos nada — el usuario puede escribir igualmente
            });
    }

    function renderSuggestions(results) {
        dropdown.innerHTML = '';

        if (!results || results.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        results.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action py-2 px-3';
            li.style.cursor = 'pointer';
            li.style.fontSize = '0.875rem';

            // Mostrar etiqueta corta; en tooltip el nombre completo
            li.textContent = item.label || item.display_name;
            li.title = item.display_name;

            li.addEventListener('mousedown', (e) => {
                // mousedown antes que blur del input
                e.preventDefault();
                input.value = item.label || item.display_name;
                lastQuery = input.value;
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            dropdown.appendChild(li);
        });

        dropdown.style.display = 'block';
    }
}
