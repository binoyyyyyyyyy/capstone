<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Show debug output
    $mail->Debugoutput = 'html';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'emailtestingsendeer@gmail.com';
    $mail->Password   = 'gknv xrds xvqb drjz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('emailtestingsendeer@gmail.com', 'NEUST Registrar');
    $mail->addAddress('emailtestingsendeer@gmail.com', 'Test Recipient');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a <b>test</b> email from PHPMailer.';
    $mail->AltBody = 'This is a test email from PHPMailer.';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo \"Message could not be sent. Mailer Error: {$mail->ErrorInfo}\";
}