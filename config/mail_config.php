<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía un correo electrónico utilizando PHPMailer.
 *
 * Esta función permite enviar correos HTML y texto plano, utilizando SMTP con Gmail.
 * Se asegura de que el correo se envíe en UTF-8 para manejar caracteres especiales como acentos y ñ.
 *
 * @param string $toEmail Dirección de correo del destinatario.
 * @param string $toName Nombre del destinatario (opcional, puede dejarse vacío).
 * @param string $subject Asunto del correo.
 * @param string $htmlBody Cuerpo del correo en HTML.
 * @param string $altBody Cuerpo alternativo en texto plano (opcional). Si no se proporciona, se genera automáticamente a partir de $htmlBody.
 * 
 * @return bool Retorna true si el correo se envió correctamente, false en caso de error.
 */
function sendMail($toEmail, $toName, $subject, $htmlBody, $altBody = ''): bool {
  $mail = new PHPMailer(true);

  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mercappbusiness@gmail.com';          // tu correo Gmail completo
    $mail->Password = 'hchz ujlz uxsu tvih';  // contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('mercappbusiness@gmail.com', 'MercaAPP');
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $altBody ?: strip_tags($htmlBody);

    $mail->send();
    return true;
  } catch (Exception $e) {
    echo "Error al enviar correo: {$mail->ErrorInfo}";
    return false;
  }
}
