<?php
session_start();

if (isset($_POST["reset"]) && !empty($_POST["email"])) {
  $email = $_POST["email"];

  try {
    // conectar a la bbdd
    $bd = new PDO("mysql:host=localhost;dbname=mercapp", "root", "");

    // comprobar si existe el usuario
    $consulta = $bd->prepare("SELECT * FROM usuario WHERE email = ?");
    $consulta->execute([$email]);

    if ($consulta->rowCount() == 1) {
      // Aquí podrías generar un token y enviarlo por correo
      // Por simplicidad, mostramos un mensaje de éxito
      $mensaje = "Se ha enviado un enlace de recuperación a tu correo.";
    } else {
      $mensaje = "No existe ninguna cuenta con ese correo.";
    }
  } catch (PDOException $e) {
    $mensaje = "Error al conectar con la base de datos.";
  }
}