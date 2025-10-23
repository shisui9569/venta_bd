<?php
session_start();
require 'conexion.php';

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

    // Verificar si la columna envio_gratis existe en detalle_pedido, si no, agregarla
    $result = $conexion->query("SHOW COLUMNS FROM detalle_pedido LIKE 'envio_gratis'");
    if ($result->num_rows == 0) {
        $conexion->query("ALTER TABLE detalle_pedido ADD COLUMN envio_gratis BOOLEAN DEFAULT FALSE");
    }

    // Guardar detalle
    $stmtDetalle = $conexion->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario, subtotal, envio_gratis) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Obtener información de envío gratis de los productos en el carrito
    $productosConEnvioGratis = [];
    if (!empty($_SESSION['carrito'])) {
        $ids = array_map(fn($item) => $item['id'], $_SESSION['carrito']);
        $placeholders = str_repeat('?', count($ids));
        $stmt = $conexion->prepare("SELECT id_producto, envio_gratis FROM productos WHERE id_producto IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $productosConEnvioGratis[$row['id_producto']] = $row['envio_gratis'];
        }
    }
    
    foreach ($_SESSION['carrito'] as $producto) {
        $subtotal = $producto['precio'] * $producto['cantidad'];
        $envio_gratis = isset($productosConEnvioGratis[$producto['id']]) ? $productosConEnvioGratis[$producto['id']] : false;
        $stmtDetalle->bind_param("iisiddi", $id_pedido, $producto['id'], $producto['nombre'], $producto['cantidad'], $producto['precio'], $subtotal, $envio_gratis);
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
