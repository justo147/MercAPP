<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
