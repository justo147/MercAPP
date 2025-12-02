<?php
// No necesitas sesión aquí, salvo que quieras guardar algo en $_SESSION
// session_start();

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$estado = "Procesando...";

try {
    $bd = new PDO("mysql:host=localhost;dbname=mercapp;charset=utf8mb4", "root", "");
    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sel = $bd->prepare("SELECT id, verify_token, email_verificado FROM usuario WHERE email = ?");
    $sel->execute([$email]);
    $u = $sel->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        $estado = "Enlace inválido.";
    } elseif ((int)$u['email_verificado'] === 1) {
        $estado = "Este correo ya está verificado.";
    } elseif ($u['verify_token'] && hash_equals($u['verify_token'], $token)) {
        $upd = $bd->prepare("UPDATE usuario SET email_verificado = 1, verify_token = NULL WHERE id = ?");
        $upd->execute([$u['id']]);
        $estado = "Correo verificado correctamente. Ya puedes iniciar sesión.";
    } else {
        $estado = "Token incorrecto.";
    }
} catch (PDOException $e) {
    error_log("Error en verificación: " . $e->getMessage());
    $estado = "Error en el servidor.";
}
