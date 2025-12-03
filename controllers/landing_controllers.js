/**
 * landing-controller.js
 *
 * Controlador de la landing page.
 * 
 * Funcionalidad:
 * - Redirige al usuario a las páginas de login o registro
 *   cuando hace clic en los botones correspondientes.
 */

document.addEventListener("DOMContentLoaded", () => {
  // Obtener referencias a los botones
  const btnLogin = document.getElementById("btn-login");
  const btnRegister = document.getElementById("btn-register");

  /**
   * Evento click para ir a la vista de login
   */
  btnLogin.addEventListener("click", () => {
    window.location.href = "auth/login.php";
  });

  /**
   * Evento click para ir a la vista de registro
   */
  btnRegister.addEventListener("click", () => {
    window.location.href = "auth/register.php";
  });
});
