<?php
require __DIR__ . '/../../config/mail_config.php';

if (sendMail('flayeresp@gmail.com', 'Prueba', 'Correo de prueba', '<p>Este es un correo de prueba desde MercApp</p>')) {
  echo "Correo enviado correctamente.";
} else {
  echo "Error al enviar correo.";
}
