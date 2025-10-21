<?php
session_start();
require_once "conexion.php";

$pedido_id = $_GET['id'] ?? 0;

// Verificar si existe la tabla de pedidos
$check_table = $conexion->query("SHOW TABLES LIKE 'pedidos'");
if ($check_table->num_rows == 0) {
    die("No se encontró información del pedido.");
}

// Obtener información del pedido
$sql = "SELECT p.*, c.nombres, c.apellidos, c.email, c.telefono, c.direccion
        FROM pedidos p 
        JOIN clientes c ON p.cliente_id = c.id_cliente 
        WHERE p.id_pedido = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

// Verificar si existe la tabla de detalle_pedido
$check_detail_table = $conexion->query("SHOW TABLES LIKE 'detalle_pedido'");
if ($check_detail_table->num_rows > 0) {
    // Obtener detalles del pedido
    $sql_detalle = "SELECT * FROM detalle_pedido WHERE pedido_id = ?";
    $stmt_detalle = $conexion->prepare($sql_detalle);
    $stmt_detalle->bind_param("i", $pedido_id);
    $stmt_detalle->execute();
    $detalles = $stmt_detalle->get_result();
} else {
    $detalles = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - SaludPerfecta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #008CBA;
            --primary-dark: #005f75;
            --success: #28a745;
            --dark: #2C3E50;
            --light: #ECF0F1;
            --white: #FFFFFF;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            color: var(--success);
            font-size: 4rem;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .order-info {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .order-info h2 {
            color: var(--primary);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-item strong {
            display: inline-block;
            width: 120px;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light);
        }
        
        th {
            background-color: var(--primary);
            color: var(--white);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: rgba(0, 140, 186, 0.05);
        }
        
        .total {
            font-size: 1.3em;
            font-weight: bold;
            text-align: right;
            padding: 15px;
            background-color: var(--light);
            border-radius: 8px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .text-center {
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="text-center" style="color: var(--success); margin-bottom: 30px;">¡Pedido Confirmado!</h1>
        
        <?php if ($pedido): ?>
            <div class="order-info">
                <h2>Información del Pedido #<?= $pedido_id ?></h2>
                <div class="info-grid">
                    <div class="info-item"><strong>Cliente:</strong> <?= $pedido['nombres'] ?> <?= $pedido['apellidos'] ?></div>
                    <div class="info-item"><strong>Email:</strong> <?= $pedido['email'] ?></div>
                    <div class="info-item"><strong>Teléfono:</strong> <?= $pedido['telefono'] ?></div>
                    <div class="info-item"><strong>Dirección:</strong> <?= $pedido['direccion'] ?></div>
                    <div class="info-item"><strong>Fecha:</strong> <?= $pedido['fecha_pedido'] ?></div>
                    <div class="info-item"><strong>Total:</strong> S/ <?= number_format($pedido['total'], 2) ?></div>
                    <div class="info-item"><strong>Método de pago:</strong> <?= ucfirst($pedido['metodo_pago']) ?></div>
                    <div class="info-item"><strong>Estado:</strong> <span style="color: var(--success);"><?= ucfirst($pedido['estado']) ?></span></div>
                </div>
            </div>

            <h2 style="color: var(--primary); margin-bottom: 15px;">Detalles del Pedido</h2>
            
            <?php if ($detalles && $detalles->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($detalle = $detalles->fetch_assoc()): ?>
                            <tr>
                                <td><?= $detalle['nombre_producto'] ?></td>
                                <td><?= $detalle['cantidad'] ?></td>
                                <td>S/ <?= number_format($detalle['precio_unitario'], 2) ?></td>
                                <td>S/ <?= number_format($detalle['cantidad'] * $detalle['precio_unitario'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="total">
                    Total: S/ <?= number_format($pedido['total'], 2) ?>
                </div>
            <?php else: ?>
                <div class="alert">
                    No se encontraron detalles del pedido.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert">
                No se encontró información del pedido.
            </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="index.php" class="btn">Volver a la Tienda</a>
            <a href="javascript:window.print()" class="btn" style="background: var(--dark);">Imprimir Comprobante</a>
        </div>
    </div>
</body>
</html>