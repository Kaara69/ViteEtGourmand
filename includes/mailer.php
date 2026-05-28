<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function envoyerEmail($destinataire, $sujet, $contenuHTML) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER; 
        $mail->Password   = MAIL_PASS; 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 2525;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('no-reply@viteetgourmand.fr', 'Vite & Gourmand');
        $mail->addAddress($destinataire);

        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $contenuHTML;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Erreur email : ' . $mail->ErrorInfo);
        return false;
    }
}