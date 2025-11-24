<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($toEmail, $toName, $subject, $htmlBody, $altBody = ''): bool
{
  $mail = new PHPMailer(true);
  $mail->SMTPDebug = 2; // o 3 para más detalle
  $mail->Debugoutput = 'html';

  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sergiodaw2025@outlook.es';
    $mail->Password = 'tuzvwpiimnwtgbrz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('sergiodaw2025@outlook.es', 'MercaAPP');
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
