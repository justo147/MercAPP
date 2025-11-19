// landing-controller.js
document.addEventListener("DOMContentLoaded", () => {
  const btnLogin = document.getElementById("btn-login");
  const btnRegister = document.getElementById("btn-register");

  // Evento para ir a la vista de login
  btnLogin.addEventListener("click", () => {
    window.location.href = "login.html";
  });

  // Evento para ir a la vista de registro
  btnRegister.addEventListener("click", () => {
    window.location.href = "register.html";
  });
});
