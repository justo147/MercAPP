 document.addEventListener("DOMContentLoaded", () => {

    function actualizarBadgeMensajes() {
        fetch("/MercApp/api/chat_unread_count.php")
        .then(res => res.text())
        .then(num => {
            const badges = document.querySelectorAll("#badge-mensajes");
            const count = parseInt(num) || 0; // Convertimos num una sola vez fuera del bucle

            badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = "inline-block";
                badge.style.opacity = "1";
            } else {
                badge.style.display = "none";
                badge.style.opacity = "0";
            }
            }); // Cierre correcto del forEach
        }) // Cierre correcto del then
        .catch(err => console.error("Error actualizando badge:", err));
    }

    // Solo mostrar el badge si el usuario está logueado
    
    actualizarBadgeMensajes();
    setInterval(actualizarBadgeMensajes, 5000);
    

});