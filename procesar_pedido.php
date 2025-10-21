<?php
session_start();
include 'conexion.php';

// ✅ FUNCIÓN PARA GENERAR QR
function generarQR($texto) {
    $tamano = 200;
    $url = "https://chart.googleapis.com/chart?cht=qr&chs={$tamano}x{$tamano}&chl=" . urlencode($texto);
    return $url;
}

// ✅ FUNCIÓN PARA GENERAR DATOS DEL QR
function generarDatosQR($codigo_pedido, $nombres, $apellidos, $documento, $total, $productos) {
    $datos = [
        'empresa' => 'SALUD PERFECTA',
        'codigo_pedido' => $codigo_pedido,
        'fecha' => date('d/m/Y H:i:s'),
        'cliente' => $nombres . ' ' . $apellidos,
        'documento' => $documento,
        'total' => 'S/ ' . number_format($total * 1.18, 2),
        'productos' => []
    ];
    
    foreach ($productos as $producto) {
        $datos['productos'][] = [
            'nombre' => $producto['nombre'],
            'cantidad' => $producto['cantidad'],
            'precio' => 'S/ ' . number_format($producto['precio'], 2)
        ];
    }
    
    return json_encode($datos, JSON_UNESCAPED_UNICODE);
}

// ✅ FUNCIÓN MEJORADA PARA ENVIAR CORREO
function enviarCorreoConfirmacion($email, $nombres, $apellidos, $codigo_pedido, $total, $productos) {
    $asunto = "✅ Confirmación de Pedido - SALUD PERFECTA - Código: $codigo_pedido";
    
    // Construir lista de productos
    $lista_productos = "";
    $contador = 1;
    foreach ($productos as $producto) {
        $subtotal = $producto['precio'] * $producto['cantidad'];
        $lista_productos .= "$contador. " . $producto['nombre'] . " \n";
        $lista_productos .= "   Cantidad: " . $producto['cantidad'] . " x S/ " . number_format($producto['precio'], 2) . " = S/ " . number_format($subtotal, 2) . "\n\n";
        $contador++;
    }
    
    $total_con_igv = $total * 1.18;
    
    $mensaje = "
¡Hola $nombres $apellidos!

Gracias por tu compra en SALUD PERFECTA. Tu pedido ha sido confirmado exitosamente.

📦 **DETALLES DEL PEDIDO:**
Código de Pedido: $codigo_pedido
Fecha: " . date('d/m/Y H:i:s') . "

🛍️ **PRODUCTOS COMPRADOS:**
$lista_productos

💰 **RESUMEN DE PAGO:**
Subtotal: S/ " . number_format($total, 2) . "
IGV (18%): S/ " . number_format($total * 0.18, 2) . "
Total: S/ " . number_format($total_con_igv, 2) . "

📋 **ESTADO DEL PEDIDO:** Pendiente de envío

Tu pedido será procesado y enviado en un plazo de 24-48 horas. Te notificaremos cuando sea despachado.

Si tienes alguna pregunta, no dudes en contactarnos.

¡Gracias por confiar en SALUD PERFECTA!

Atentamente,
El equipo de SALUD PERFECTA
📞 Teléfono: +51 123 456 789
📧 Email: shisui18gol@gmail.com
    ";
    
    // CABECERAS MEJORADAS CON TU CORREO
    $headers = "From: SALUD PERFECTA <shisui18gol@gmail.com>" . "\r\n" .
               "Reply-To: shisui18gol@gmail.com" . "\r\n" .
               "Return-Path: shisui18gol@gmail.com" . "\r\n" .
               "X-Mailer: PHP/" . phpversion() . "\r\n" .
               "MIME-Version: 1.0" . "\r\n" .
               "Content-Type: text/plain; charset=UTF-8" . "\r\n" .
               "X-Priority: 1" . "\r\n" .
               "Importance: High";
    
    // FORZAR EL ENVÍO
    ini_set('sendmail_from', 'shisui18gol@gmail.com');
    
    // Intentar enviar el correo
    if (mail($email, $asunto, $mensaje, $headers)) {
        usleep(500000);
        return true;
    } else {
        error_log("❌ ERROR: No se pudo enviar correo a: $email");
        sleep(1);
        if (mail($email, $asunto, $mensaje, $headers)) {
            return true;
        }
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['carrito'])) {
    header("Location: carrito.php");
    exit();
}

// Validar campos
$campos = ['tipo_documento','nro_documento','nombres','apellidos','email','telefono','direccion','departamento','provincia','distrito','metodo_pago'];
foreach ($campos as $c) {
    if (empty($_POST[$c])) die("❌ Falta el campo: $c");
}

// Variables
$tipo_documento = $_POST['tipo_documento'];
$nro_documento  = $_POST['nro_documento'];
$nombres        = $_POST['nombres'];
$apellidos      = $_POST['apellidos'];
$email          = $_POST['email'];
$telefono       = $_POST['telefono'];
$direccion      = $_POST['direccion'];
$departamento   = $_POST['departamento'];
$provincia      = $_POST['provincia'];
$distrito       = $_POST['distrito'];
$metodo_pago    = $_POST['metodo_pago'];
$detalles_direccion = $_POST['detalles_direccion'] ?? '';
$tipo_entrega   = $_POST['tipo_entrega'] ?? 'express';

// Validación DNI (8 dígitos)
if ($tipo_documento === 'DNI') {
    $nro_documento = preg_replace('/\D/', '', $nro_documento);
    if (strlen($nro_documento) !== 8) {
        die("❌ El DNI debe tener exactamente 8 dígitos");
    }
}

$conexion->begin_transaction();

try {
    // CLIENTE
    $stmt = $conexion->prepare("SELECT id_cliente FROM cliente WHERE nro_documento=? OR email=? LIMIT 1");
    $stmt->bind_param("ss", $nro_documento, $email);
    $stmt->execute();
    $stmt->bind_result($id_cliente);
    if (!$stmt->fetch()) {
        $stmt->close();
        
        $check_column = $conexion->query("SHOW COLUMNS FROM cliente LIKE 'detalles_direccion'");
        if ($check_column->num_rows > 0) {
            $stmt = $conexion->prepare("INSERT INTO cliente (tipo_documento,nro_documento,nombres,apellidos,email,telefono,direccion,departamento,provincia,distrito,detalles_direccion) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssssss",$tipo_documento,$nro_documento,$nombres,$apellidos,$email,$telefono,$direccion,$departamento,$provincia,$distrito,$detalles_direccion);
        } else {
            $stmt = $conexion->prepare("INSERT INTO cliente (tipo_documento,nro_documento,nombres,apellidos,email,telefono,direccion,departamento,provincia,distrito) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssssss",$tipo_documento,$nro_documento,$nombres,$apellidos,$email,$telefono,$direccion,$departamento,$provincia,$distrito);
        }
        
        $stmt->execute();
        $id_cliente = $stmt->insert_id;
    }
    $stmt->close();

    // PEDIDO
    $codigo_pedido = 'PED-' . strtoupper(uniqid());
    $costo_envio = 0.00;
    $descuento = 0.00;
    $total = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    $estado = 'pendiente';

    $check_tipo_entrega = $conexion->query("SHOW COLUMNS FROM pedido LIKE 'tipo_entrega'");
    if ($check_tipo_entrega->num_rows > 0) {
        $stmtPedido = $conexion->prepare("INSERT INTO pedido (id_cliente,codigo_pedido,direccion_envio,costo_envio,descuento,total,metodo_pago,estado,tipo_entrega) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmtPedido->bind_param("issdddsss",$id_cliente,$codigo_pedido,$direccion,$costo_envio,$descuento,$total,$metodo_pago,$estado,$tipo_entrega);
    } else {
        $stmtPedido = $conexion->prepare("INSERT INTO pedido (id_cliente,codigo_pedido,direccion_envio,costo_envio,descuento,total,metodo_pago,estado) VALUES (?,?,?,?,?,?,?,?)");
        $stmtPedido->bind_param("issdddss",$id_cliente,$codigo_pedido,$direccion,$costo_envio,$descuento,$total,$metodo_pago,$estado);
    }
    
    $stmtPedido->execute();
    $id_pedido = $stmtPedido->insert_id;
    $stmtPedido->close();

    // DETALLE
    $stmtDet = $conexion->prepare("INSERT INTO detalle_pedido (id_pedido,id_producto,nombre_producto,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?,?)");
    foreach ($_SESSION['carrito'] as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtDet->bind_param("iissdd",$id_pedido,$item['id'],$item['nombre'],$item['cantidad'],$item['precio'],$subtotal);
        $stmtDet->execute();
    }
    $stmtDet->close();

    // ACTUALIZAR STOCK
    foreach ($_SESSION['carrito'] as $item) {
        $id_producto = $item['id'];
        $cantidad_comprada = $item['cantidad'];
        
        $stmt_update_stock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
        $stmt_update_stock->bind_param("ii", $cantidad_comprada, $id_producto);
        $stmt_update_stock->execute();
        
        $stmt_check_stock = $conexion->prepare("SELECT stock FROM productos WHERE id_producto = ?");
        $stmt_check_stock->bind_param("i", $id_producto);
        $stmt_check_stock->execute();
        $stock_result = $stmt_check_stock->get_result();
        
        if ($stock_row = $stock_result->fetch_assoc()) {
            if ($stock_row['stock'] < 0) {
                $stmt_fix_stock = $conexion->prepare("UPDATE productos SET stock = 0 WHERE id_producto = ?");
                $stmt_fix_stock->bind_param("i", $id_producto);
                $stmt_fix_stock->execute();
                $stmt_fix_stock->close();
            }
        }
        
        $stmt_update_stock->close();
        $stmt_check_stock->close();
    }

    $conexion->commit();

    // ENVIAR CORREO
    $correo_enviado = enviarCorreoConfirmacion($email, $nombres, $apellidos, $codigo_pedido, $total, $_SESSION['carrito']);

    // GENERAR QR
    $datos_qr = generarDatosQR($codigo_pedido, $nombres, $apellidos, $nro_documento, $total, $_SESSION['carrito']);
    $qr_url = generarQR($datos_qr);

    // ENLACE GMAIL
    $asunto_gmail = "✅ CONFIRMACIÓN DE PEDIDO - SALUD PERFECTA - " . $codigo_pedido;
    $cuerpo_gmail = "¡Hola $nombres $apellidos!\n\nGracias por tu compra en SALUD PERFECTA.\nCódigo: $codigo_pedido\nFecha: " . date('d/m/Y H:i:s');

    $datos_pedido = [
        'codigo_pedido' => $codigo_pedido,
        'id_pedido' => $id_pedido,
        'fecha' => date('d/m/Y H:i:s'),
        'cliente' => [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'tipo_documento' => $tipo_documento,
            'nro_documento' => $nro_documento,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'departamento' => $departamento,
            'provincia' => $provincia,
            'distrito' => $distrito,
            'detalles_direccion' => $detalles_direccion
        ],
        'entrega' => [
            'tipo' => $tipo_entrega,
            'direccion_completa' => $direccion . ($detalles_direccion ? ', ' . $detalles_direccion : '') . ', ' . $distrito . ', ' . $provincia . ', ' . $departamento
        ],
        'pago' => [
            'metodo' => $metodo_pago,
            'total' => $total
        ],
        'productos' => $_SESSION['carrito'],
        'correo_enviado' => $correo_enviado,
        'qr_url' => $qr_url,
        'datos_qr' => $datos_qr,
        'enlace_gmail' => "https://mail.google.com/mail/?view=cm&fs=1&to=" . urlencode($email) . "&su=" . urlencode($asunto_gmail) . "&body=" . urlencode($cuerpo_gmail)
    ];

    $_SESSION['carrito'] = [];

} catch (Exception $e) {
    $conexion->rollback();
    die("❌ Error al procesar pedido: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - <?= $datos_pedido['codigo_pedido'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
            padding: 20px;
            min-height: 100vh;
            color: #1f2937;
        }

        .invoice-container {
            max-width: 950px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        /* HEADER DE LA CONFIRMACIÓN */
        .invoice-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .invoice-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 25px;
        }

        .company-info {
            flex: 1;
            min-width: 300px;
        }

        .company-info h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .company-info p {
            font-size: 15px;
            opacity: 0.9;
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .invoice-details {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            min-width: 250px;
            text-align: right;
        }

        .invoice-number {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            font-family: 'Inter', monospace;
            word-break: break-all;
        }

        .invoice-date {
            font-size: 15px;
            opacity: 0.9;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.2);
            color: #d97706;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* ALERT DE CORREO */
        .alert {
            padding: 20px 30px;
            margin: 25px 30px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #10b981;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef9c3 0%, #feedba 100%);
            border-color: #f59e0b;
        }

        .alert i {
            font-size: 24px;
            min-width: 24px;
        }

        .alert-content strong {
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        /* SECCIONES DE LA CONFIRMACIÓN */
        .invoice-section {
            padding: 35px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .invoice-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, #10b981, transparent);
            margin-left: 15px;
        }

        .section-title i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        /* GRID DE INFORMACIÓN */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 15px;
        }

        .info-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f9fafb;
            padding: 6px 10px;
            border-radius: 8px;
            display: inline-block;
            width: fit-content;
        }

        .info-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 500;
            padding: 8px 0;
            word-break: break-word;
        }

        /* TABLA DE PRODUCTOS */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .products-table thead th {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 16px 20px;
            text-align: left;
            font-size: 14px;
            font-weight: 700;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #d1fae5;
        }

        .products-table tbody td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .products-table tbody tr:last-child td {
            border-bottom: none;
        }

        .products-table tbody tr:hover {
            background: #f0fdf4;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
        }

        .product-name {
            font-weight: 600;
            color: #1f2937;
            min-width: 200px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* RESUMEN DE TOTALES */
        .totals-section {
            margin-top: 25px;
            padding: 25px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }

        .total-row.subtotal {
            padding-top: 0;
        }

        .total-label {
            color: #4b5563;
            font-weight: 500;
        }

        .total-value {
            font-weight: 600;
            color: #1f2937;
        }

        .total-row.discount {
            color: #10b981;
            font-weight: 600;
        }

        .total-row.shipping {
            color: #6b7280;
        }

        .total-row.grand-total {
            border-top: 2px solid #d1fae5;
            padding-top: 18px;
            margin-top: 15px;
            font-size: 20px;
            font-weight: 800;
            color: #059669;
        }

        .total-row.grand-total .total-value {
            font-size: 24px;
            color: #059669;
        }

        .free-shipping {
            color: #10b981 !important;
            font-weight: 700 !important;
        }

        /* QR CODE */
        .qr-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 15px;
            margin-top: 25px;
            border: 1px solid #d1fae5;
        }

        .qr-code-img {
            max-width: 200px;
            border: 4px solid #10b981;
            border-radius: 15px;
            padding: 12px;
            background: white;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
            margin: 0 auto 15px;
        }

        .qr-instructions {
            margin-top: 15px;
            font-size: 14px;
            color: #4b5563;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* MENSAJE FINAL */
        .thank-you {
            text-align: center;
            padding: 45px 35px;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #065f46;
            border-radius: 0 0 20px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .thank-you h3 {
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .thank-you p {
            font-size: 16px;
            margin: 8px 0;
            line-height: 1.6;
        }

        .delivery-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-weight: 600;
        }

        /* BOTONES DE ACCIÓN */
        .action-buttons {
            display: flex;
            gap: 18px;
            padding: 35px;
            background: #f8fafc;
            flex-wrap: wrap;
            justify-content: center;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-width: 160px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: #1f2937;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .btn-full-width {
            width: 100%;
            justify-content: center;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            body {
                padding: 15px;
                font-size: 14px;
            }

            .invoice-header {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 20px;
            }

            .invoice-details {
                text-align: left;
                min-width: auto;
            }

            .invoice-section {
                padding: 25px 20px;
            }

            .alert {
                padding: 15px 20px;
                margin: 20px 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .products-table {
                font-size: 13px;
            }

            .products-table th,
            .products-table td {
                padding: 12px 12px;
            }

            .action-buttons {
                padding: 25px 20px;
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
                padding: 16px;
            }

            .company-info h1 {
                font-size: 28px;
            }

            .invoice-number {
                font-size: 22px;
            }

            .section-title {
                font-size: 16px;
            }

            .totals-section {
                padding: 20px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 10px;
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .invoice-container {
                box-shadow: none;
                border-radius: 0;
                border: 1px solid #000;
                max-width: 100%;
                margin: 0 auto;
                width: 100%;
            }

            /* RECEIPT FORMAT FOR PRINT */
            .invoice-header {
                background: white !important;
                color: black !important;
                padding: 8px;
                text-align: center;
                border-bottom: 2px solid #000;
            }

            .header-content {
                flex-direction: column !important;
                gap: 5px !important;
            }

            .company-info {
                min-width: auto !important;
            }

            .company-info h1 {
                color: black !important;
                font-size: 18px !important;
                margin-bottom: 3px;
            }

            .company-info p {
                color: black !important;
                font-size: 8px !important;
                margin: 1px 0;
            }

            .invoice-details {
                background: none !important;
                padding: 8px 5px !important;
                text-align: center;
            }

            .invoice-number {
                font-size: 14px !important;
                color: black !important;
                font-weight: bold;
            }

            .invoice-date {
                font-size: 9px !important;
                color: black !important;
            }

            .status-badge {
                display: none !important;
            }

            .section-title {
                color: black !important;
                font-size: 12px !important;
                font-weight: bold;
                margin-bottom: 5px;
                padding: 5px 0;
            }

            .section-title::after {
                display: none;
            }

            .section-title i {
                display: none;
            }

            .invoice-section {
                padding: 10px;
                border-bottom: 1px solid #ccc;
            }

            .info-grid {
                display: block;
            }

            .info-row {
                margin-bottom: 4px;
            }

            .info-label {
                font-size: 8px !important;
                color: #4b5563 !important;
                padding: 2px 4px !important;
            }

            .info-value {
                font-size: 9px !important;
                color: #1f2937 !important;
                padding: 2px 0 !important;
            }

            .products-table {
                font-size: 9px;
                margin-top: 8px;
                width: 100%;
            }

            .products-table th {
                font-size: 7px;
                padding: 3px;
            }

            .products-table td {
                padding: 2px;
            }

            .product-name {
                font-size: 9px !important;
            }

            .totals-section {
                padding: 8px;
                background: white !important;
                border: 1px solid #ccc;
                margin-top: 8px;
            }

            .total-row {
                font-size: 10px;
                padding: 2px 0;
            }

            .total-label {
                font-size: 8px;
            }

            .total-value {
                font-size: 10px;
            }

            .total-row.grand-total {
                font-size: 12px;
                border-top: 2px solid #000;
            }

            .total-row.grand-total .total-value {
                font-size: 14px;
                font-weight: bold;
            }

            .qr-section {
                display: none;
            }

            .thank-you {
                background: white !important;
                color: black !important;
                padding: 10px;
                border-top: 1px solid #ccc;
                text-align: center;
            }

            .thank-you h3 {
                font-size: 12px !important;
                color: black !important;
                margin-bottom: 4px;
            }

            .thank-you p {
                font-size: 8px !important;
                color: black !important;
                margin: 2px 0;
            }

            .delivery-info {
                font-size: 8px !important;
                margin-top: 5px;
            }

            .action-buttons {
                display: none;
            }

            .alert {
                display: none;
            }

            .badge {
                font-size: 6px !important;
                padding: 1px 3px !important;
            }
            
            /* Hide non-essential elements */
            .fas, .far {
                font-size: 8px;
            }
        }

        /* BADGES MODERNOS */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            min-width: 80px;
        }

        .badge-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .badge-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .badge-warning {
            background: linear-gradient(135deg, #fef9c3 0%, #fcd34d 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .badge-processing {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            color: #0891b2;
            border: 1px solid #0ea5e9;
        }

        /* ANIMACIONES */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        .delivery-info {
            animation: pulse 3s infinite;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- HEADER -->
        <div class="invoice-header">
            <div class="header-content">
                <div class="company-info">
                    <h1>SALUD PERFECTA</h1>
                    <p><i class="fas fa-store"></i> Productos de Salud de Primera Clase</p>
                    <p><i class="fas fa-map-marker-alt"></i> Av. Principal 1234, Lima, Perú</p>
                    <p><i class="fas fa-phone"></i> +51 987 654 321</p>
                    <p><i class="fas fa-envelope"></i> atencion@saludperfecta.com</p>
                </div>
                <div class="invoice-details">
                    <div class="invoice-number pulse-animation"><?= $datos_pedido['codigo_pedido'] ?></div>
                    <div class="invoice-date">
                        <i class="fas fa-calendar-alt"></i> <?= $datos_pedido['fecha'] ?>
                    </div>
                    <div style="margin-top: 12px;">
                        <span class="status-badge status-pending">Pedido Confirmado</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ALERT CORREO -->
        <?php if ($datos_pedido['correo_enviado']): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div class="alert-content">
                <strong>Correo de confirmación enviado exitosamente</strong>
                <small>Se ha enviado una copia de tu pedido a <?= $email ?></small>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="alert-content">
                <strong>Correo pendiente de envío</strong>
                <small>Guarda este código: <strong style="font-weight: 700;"><?= $datos_pedido['codigo_pedido'] ?></strong> como referencia</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- DATOS DEL CLIENTE -->
        <div class="invoice-section">
            <div class="section-title">
                <i class="fas fa-user-circle"></i>
                Información del Cliente
            </div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nombre Completo</div>
                    <div class="info-value"><?= $datos_pedido['cliente']['nombres'] ?> <?= $datos_pedido['cliente']['apellidos'] ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><?= $datos_pedido['cliente']['tipo_documento'] ?></div>
                    <div class="info-value"><?= $datos_pedido['cliente']['nro_documento'] ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Correo Electrónico</div>
                    <div class="info-value"><?= $datos_pedido['cliente']['email'] ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?= $datos_pedido['cliente']['telefono'] ?></div>
                </div>
            </div>
        </div>

        <!-- DATOS DE ENVÍO -->
        <div class="invoice-section">
            <div class="section-title">
                <i class="fas fa-shipping-fast"></i>
                Información de Envío
                <span class="badge badge-info" style="margin-left: auto;"><?= strtoupper($datos_pedido['entrega']['tipo']) ?></span>
            </div>
            <div class="info-grid">
                <div class="info-row" style="grid-column: 1 / -1;">
                    <div class="info-label">Dirección Completa</div>
                    <div class="info-value"><?= $datos_pedido['entrega']['direccion_completa'] ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Método de Pago</div>
                    <div class="info-value"><?= $datos_pedido['pago']['metodo'] ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tiempo de Entrega</div>
                    <div class="info-value">24 - 48 horas hábiles</div>
                </div>
            </div>
        </div>

        <!-- PRODUCTOS -->
        <div class="invoice-section">
            <div class="section-title">
                <i class="fas fa-box-open"></i>
                Detalle de Productos
            </div>

            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Producto</th>
                        <th class="text-center" style="width: 15%;">Cantidad</th>
                        <th class="text-right" style="width: 17.5%;">Precio Unit.</th>
                        <th class="text-right" style="width: 17.5%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos_pedido['productos'] as $producto): ?>
                    <tr>
                        <td class="product-name"><?= htmlspecialchars($producto['nombre']) ?></td>
                        <td class="text-center"><?= $producto['cantidad'] ?></td>
                        <td class="text-right">S/ <?= number_format($producto['precio'], 2) ?></td>
                        <td class="text-right" style="font-weight: 600;">S/ <?= number_format($producto['precio'] * $producto['cantidad'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TOTALES -->
            <div class="totals-section">
                <div class="total-row subtotal">
                    <div class="total-label">Subtotal:</div>
                    <div class="total-value">S/ <?= number_format($datos_pedido['pago']['total'], 2) ?></div>
                </div>
                
                <?php if (isset($_POST['monto_descuento']) && $_POST['monto_descuento'] > 0): ?>
                <div class="total-row discount">
                    <div class="total-label">Descuento:</div>
                    <div class="total-value">-S/ <?= number_format($_POST['monto_descuento'], 2) ?></div>
                </div>
                <?php endif; ?>

                <div class="total-row shipping">
                    <div class="total-label">Costo de Envío:</div>
                    <div class="total-value free-shipping">GRATIS</div>
                </div>
                
                <div class="total-row">
                    <div class="total-label">IGV (18%):</div>
                    <div class="total-value">S/ <?= number_format($datos_pedido['pago']['total'] * 0.18, 2) ?></div>
                </div>
                
                <div class="total-row grand-total">
                    <div class="total-label">TOTAL:</div>
                    <div class="total-value">S/ <?= number_format($datos_pedido['pago']['total'] * 1.18, 2) ?></div>
                </div>
            </div>
        </div>

        <!-- CÓDIGO QR -->
        <div class="invoice-section">
            <div class="section-title">
                <i class="fas fa-qrcode"></i>
                Código de Verificación
            </div>
            
            <div class="qr-section">
                <img src="<?= $datos_pedido['qr_url'] ?>" alt="Código QR de Verificación" class="qr-code-img">
                <div class="qr-instructions">
                    <i class="fas fa-mobile-alt"></i> Escanea este código QR con tu cámara para verificar los detalles de tu pedido
                </div>
            </div>
        </div>

        <!-- MENSAJE FINAL -->
        <div class="thank-you">
            <h3><i class="fas fa-heart" style="color: #10b981;"></i> ¡Gracias por tu compra!</h3>
            <p><strong>SALUD PERFECTA</strong> agradece tu preferencia y confianza</p>
            <p>Tu pedido está siendo procesado y se enviará en las próximas 24-48 horas hábiles</p>
            <?php if ($datos_pedido['correo_enviado']): ?>
            <p style="margin-top: 15px; line-height: 1.6;">
                <i class="fas fa-envelope-open-text"></i> 
                Hemos enviado una confirmación completa a <strong style="color: #059669;"><?= $datos_pedido['cliente']['email'] ?></strong><br>
                <small style="color: #065f46; font-size: 14px;">También puedes verificar tu pedido usando el código QR o guardando esta página</small>
            </p>
            <?php endif; ?>
            <div class="delivery-info">
                <i class="fas fa-truck-loading"></i>
                <span>Tu pedido llegará en 24-48 horas hábiles</span>
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i>
                Imprimir Confirmación
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-shopping-bag"></i>
                Continuar Comprando
            </a>
            <?php if (!$datos_pedido['correo_enviado']): ?>
            <button class="btn btn-success" onclick="reenviarCorreo()">
                <i class="fas fa-paper-plane"></i>
                Reenviar Confirmación
            </button>
            <?php endif; ?>
            <button class="btn btn-secondary" onclick="descargarPDF()">
                <i class="fas fa-file-download"></i>
                Guardar PDF
            </button>
        </div>
    </div>

    <script>
        // Función para reenviar correo
        function reenviarCorreo() {
            if (confirm('¿Deseas intentar reenviar la confirmación de pedido?')) {
                alert('📧 Estamos intentando reenviar tu confirmación.\n\nPor favor, contacta a:\n\natencion@saludperfecta.com\n\nCon tu código de pedido:\n<?= $datos_pedido['codigo_pedido'] ?>');
            }
        }

        // Función para descargar PDF
        function descargarPDF() {
            alert('📄 Para guardar como PDF:\n\n1. Pulsa Ctrl + P (o Cmd + P en Mac)\n2. Selecciona "Guardar como PDF" en las opciones de impresión\n3. Elige la ubicación y guarda el archivo');
        }

        // Animación de entrada
        window.addEventListener('load', function() {
            const container = document.querySelector('.invoice-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });

        // Confirmación de impresión
        window.onbeforeprint = function() {
            document.body.style.backgroundColor = 'white';
            console.log('Preparando impresión de confirmación: <?= $datos_pedido['codigo_pedido'] ?>');
        };

        window.onafterprint = function() {
            document.body.style.backgroundColor = '';
            console.log('Impresión completada');
        };

        // Prevenir doble clic en botones
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.classList.contains('processing')) {
                    e.preventDefault();
                    return false;
                }
                
                this.classList.add('processing');
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                this.style.opacity = '0.8';
                
                setTimeout(() => {
                    this.classList.remove('processing');
                    this.innerHTML = originalText;
                    this.style.opacity = '1';
                }, 2000);
            });
        });

        // Efecto hover en filas de productos
        document.querySelectorAll('.products-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.15)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Copiar código de pedido al hacer clic
        document.querySelector('.invoice-number').addEventListener('click', function() {
            const codigo = this.textContent;
            navigator.clipboard.writeText(codigo).then(() => {
                const originalText = this.textContent;
                const originalColor = this.style.color;
                this.textContent = '✓ ¡Copiado!';
                this.style.color = '#10b981';
                this.style.cursor = 'default';
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.color = originalColor;
                    this.style.cursor = 'pointer';
                }, 2000);
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        });

        // Log de información
        console.log('%c✅ Confirmación de pedido generada exitosamente', 'color: #10b981; font-size: 16px; font-weight: bold;');
        console.log('Código de pedido:', '<?= $datos_pedido['codigo_pedido'] ?>');
        console.log('Cliente:', '<?= $datos_pedido['cliente']['nombres'] ?> <?= $datos_pedido['cliente']['apellidos'] ?>');
        console.log('Total:', 'S/ <?= number_format($datos_pedido['pago']['total'] * 1.18, 2) ?>');
        console.log('Correo enviado:', <?= $datos_pedido['correo_enviado'] ? 'true' : 'false' ?>);

        // Añadir animaciones de entrada a secciones
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.invoice-section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(section);
        });
    </script>
</body>
</html>