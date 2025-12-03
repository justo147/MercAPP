<?php
/**
 * logout.php
 *
 * Script para cerrar sesión de usuario.
 *
 * Funcionalidad:
 * - Inicia la sesión si no está iniciada.
 * - Destruye toda la sesión y sus variables.
 * - Redirige al usuario a la página principal (landing page).
 */

session_start(); // Iniciar la sesión para poder destruirla
session_destroy(); // Eliminar todas las variables de sesión

// Redirigir al usuario a la landing page
header("location: ../public/views/landing_page.php");
exit; // Asegurar que no se ejecute código adicional
