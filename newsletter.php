<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $emailUsuario = $_POST['email'];

    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tomiinchausps01@gmail.com';     
        $mail->Password   = 'ttcvooqfrrddlqaxj';         
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('tomiinchausps01@gmail.com', 'ttcvooqfrrddlqaxj');


        $mail->addAddress($emailUsuario);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Gracias por suscribirte a AgenciaViajes';
        $mail->Body    = '<h2>¡Gracias por suscribirte!</h2><p>Pronto recibirás novedades y ofertas exclusivas.</p>';
        $mail->AltBody = 'Gracias por suscribirte a AgenciaViajes.';

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => "Error al enviar: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida.']);
}
