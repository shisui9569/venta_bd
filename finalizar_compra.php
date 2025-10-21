<?php
session_start();
require 'conexion.';

// Si el carrito está vacío, redirigir
if (!isset($_SESSION['carrito']) || count($_SESSION['carrito']) === 0) {
    die("Tu carrito está vacío.");
}

// Verificar que el formulario se envió
if (isset($_POST['finalizar'])) {
    $nombre    = $_POST['nombre'];
    $email     = $_POST['email'];
    $direccion = $_POST['direccion'];

    // Calcular total del carrito
    $total = 0;
    foreach ($_SESSION['carrito'] as $producto) {
        $total += $producto['precio'] * $producto['cantidad'];
    }

    // Guardar pedido en la BD (simplificado)
    $stmt = $conexion->prepare("INSERT INTO pedidos (id_cliente, fecha_pedido, total, estado) VALUES (?, NOW(), ?, 'pendiente')");
    $id_cliente = 1; // Simulación, cámbialo según tu login
    $stmt->bind_param("id", $id_cliente, $total);
    $stmt->execute();
    $id_pedido = $stmt->insert_id;

    // Guardar detalle
    foreach ($_SESSION['carrito'] as $producto) {
        $stmtDetalle = $conexion->prepare("INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, precio) VALUES (?, ?, ?, ?)");
        $stmtDetalle->bind_param("iiid", $id_pedido, $producto['id'], $producto['cantidad'], $producto['precio']);
        $stmtDetalle->execute();
    }

    // ======================
    // 📧 ENVIAR EMAIL
    // ======================
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_correo@gmail.com'; // tu Gmail
        $mail->Password   = 'tu_contraseña_app';   // clave de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente
        $mail->setFrom('tu_correo@gmail.com', 'SaludPerfecta');

        // Destinatario
        $mail->addAddress($email, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = "✅ Pedido Exitoso - SaludPerfecta";
        $mail->Body    = "
            <h2>¡Gracias por tu compra, $nombre!</h2>
            <p>Hemos recibido tu pedido <b>#$id_pedido</b>.</p>
            <p>Total: <b>S/ ".number_format($total, 2)."</b></p>
            <p>Lo enviaremos a: <b>$direccion</b></p>
            <p>Pronto recibirás novedades del envío.</p>
        ";

        $mail->send();
        echo "✅ Pedido realizado y correo enviado a $email";
        $_SESSION['carrito'] = []; // Vaciar carrito
    } catch (Exception $e) {
        echo "❌ Error al enviar correo: {$mail->ErrorInfo}";
    }
}
?>
