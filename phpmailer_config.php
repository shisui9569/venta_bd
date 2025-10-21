<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Si usas Composer

function enviarCorreoSMTP($email, $nombres, $apellidos, $codigo_pedido, $total, $productos) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // O tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'tu_email@gmail.com';
        $mail->Password = 'tu_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Remitente y destinatario
        $mail->setFrom('no-reply@plazavea.com', 'PLAZA VEA');
        $mail->addAddress($email, $nombres . ' ' . $apellidos);
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = "✅ Confirmación de Pedido - $codigo_pedido";
        
        // Construir HTML del correo
        $mail->Body = construirHTMLCorreo($nombres, $apellidos, $codigo_pedido, $total, $productos);
        $mail->AltBody = construirTextoCorreo($nombres, $apellidos, $codigo_pedido, $total, $productos);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
?>