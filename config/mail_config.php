<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($toEmail, $toName, $subject, $htmlBody, $altBody = ''): bool {
  $mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host = 'smtp.office365.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'justo@outlook.com';
  $mail->Password = 'tu_contraseña_de_aplicación';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  $mail->setFrom('justo@outlook.com', 'MercaAPP');
  $mail->addAddress($toEmail, $toName);

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $htmlBody;
  $mail->AltBody = $altBody ?: strip_tags($htmlBody);

  $mail->send();
  return true;
} catch (Exception $e) {
  error_log('Mailer Error: ' . $mail->ErrorInfo);
  return false;
}

}
