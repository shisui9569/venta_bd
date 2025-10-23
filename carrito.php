<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Mantengo el manejo original de "Actualizar cantidades"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    foreach ($_SESSION['carrito'] as &$item) {
        $id = $item['id'];
        $nueva = isset($_POST['cantidad'][$id]) ? max(1, (int)$_POST['cantidad'][$id]) : $item['cantidad'];
        $item['cantidad'] = $nueva;
    }
    header("Location: carrito.php");
    exit();
}

// Eliminar producto
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $_SESSION['carrito'] = array_values(array_filter($_SESSION['carrito'], fn($i) => $i['id'] != $id));
    header("Location: carrito.php");
    exit();
}

// Verificar si la columna envio_gratis existe, si no, agregarla
$result = $conexion->query("SHOW COLUMNS FROM productos LIKE 'envio_gratis'");
if ($result->num_rows == 0) {
    $conexion->query("ALTER TABLE productos ADD COLUMN envio_gratis BOOLEAN DEFAULT FALSE");
}

// Obtener imágenes y envío gratis de los productos desde la base de datos
$productosConInfo = [];
if (!empty($_SESSION['carrito'])) {
    $ids = array_map(fn($item) => $item['id'], $_SESSION['carrito']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $conexion->prepare("SELECT id_producto, imagen, envio_gratis FROM productos WHERE id_producto IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $productosConInfo[$row['id_producto']] = [
            'imagen' => $row['imagen'],
            'envio_gratis' => $row['envio_gratis']
        ];
    }
}

// Cálculos
$total = 0;
$descuento_total = 0;

foreach ($_SESSION['carrito'] as $item) {
    $subtotal = $item['precio'] * $item['cantidad'];
    $total += $subtotal;
    
    // Calcular descuento si existe precio regular
    if (isset($item['precio_regular']) && $item['precio_regular'] > $item['precio']) {
        $descuento_por_producto = ($item['precio_regular'] - $item['precio']) * $item['cantidad'];
        $descuento_total += $descuento_por_producto;
    }
}

// Calcular envío considerando productos con envío gratis
$envio = 0;
$hay_envio_gratis_global = true; // Asumimos que todos tienen envío gratis hasta probar lo contrario

if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $producto_id = $item['id'];
        if (isset($productosConInfo[$producto_id]) && !$productosConInfo[$producto_id]['envio_gratis']) {
            $hay_envio_gratis_global = false; // Si cualquier producto no tiene envío gratis, todo el pedido requiere envío
            break;
        }
    }
    
    // Si no todos los productos tienen envío gratis, calcular el envío normal
    if (!$hay_envio_gratis_global) {
        $envio = $total >= 100 ? 0 : 20; // Ofrecer envío gratis por compras >= 100 soles
    }
    // Si todos los productos tienen envío gratis, $envio ya es 0
}

$gran_total = $total + $envio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Carrito de Compras • Salud Perfecta</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;900&family=Exo+2:wght@300;400;500;600;700&display=swap');

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: 'Exo 2', 'Roboto', 'Helvetica Neue', Arial, sans-serif; 
            background: #f8f9fa; 
            color: #333; 
            line-height: 1.5;
            overflow-x: hidden;
        }
        
        a { 
            color: inherit; 
            text-decoration: none; 
        }

        :root {
            --primary: #005f73;
            --secondary: #0a9396;
            --accent: #94d2bd;
            --dark: #001219;
            --light: #e9d8a6;
            --white: #ffffff;
            --red-accent: #ff0000;
            --info-blue: #1976d2;
            --text-color: #333;
            --light-text: #f8f9fa;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.2);
            --gradient-1: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            --gradient-2: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            --gradient-3: linear-gradient(135deg, var(--accent) 0%, var(--light) 100%);
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(148, 210, 189, 0.3);
            --neon-cyan: #00ffff;
            --neon-purple: #c724b1;
            --neon-pink: #ff2d95;
            --text-secondary: #cccccc;
            --gris-500: #6b7280;
            --gris-600: #4b5563;
            --gris-700: #374151;
        }

        /* HEADER */
        .commercial-header {
            background: linear-gradient(135deg, var(--gris-500) 0%, var(--gris-600) 100%);
            padding: 12px 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .commercial-header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .commercial-logo {
            font-family: 'Orbitron', monospace;
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .commercial-logo span {
            color: #ffcc00;
        }

        .commercial-nav {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .commercial-nav-item {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .commercial-nav-item:hover {
            color: #ffcc00;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        /* CONTENIDO PRINCIPAL */
        .container {
            max-width: 1200px;
            margin: 120px auto 20px;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
            align-items: start;
        }
        
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* STEPPER */
        .stepper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            margin: 80px 0 20px;
            position: relative;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .step.active .step-number {
            background: #e30044;
            color: white;
            border-color: #e30044;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .step-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #e30044;
            font-weight: 600;
        }
        
        .stepper-line {
            position: absolute;
            top: 20px;
            left: 80px;
            right: 80px;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }

        /* CARDS */
        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border: 1px solid #eaeaea;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: #333;
        }
        
        .step-indicator {
            font-size: 14px;
            color: #666;
        }

        /* TABLA DE PRODUCTOS */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .product-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .product-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eaeaea;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-details h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        
        .product-details p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }

        /* BOTONES */
        .btn-primary {
            background: #e30044;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #c1003a;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        /* SIDEBAR */
        .sidebar {
            position: sticky;
            top: 20px;
        }
        
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
        }
        
        .summary-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #555;
            font-size: 15px;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #eee;
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .shipping-free {
            color: #28a745;
            font-weight: 600;
        }
        
        .shipping-cost {
            color: #dc3545;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        /* ESTADOS - MODIFICADO PARA MOSTRAR TODOS LOS PASOS */
        .step-section {
            display: block !important;
        }

        .hidden {
            display: none !important;
        }

        /* CARRITO VACÍO */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-cart i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .empty-cart h3 {
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .empty-cart p {
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* FORMULARIOS */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #e30044;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* MÉTODOS DE PAGO */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        
        .payment-method:hover {
            border-color: #e30044;
        }
        
        .payment-method.selected {
            border-color: #e30044;
            background: #fff5f7;
        }
        
        .payment-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .payment-name {
            font-size: 14px;
            font-weight: 500;
        }

        /* LOADING SPINNER */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e30044;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container {
                margin-top: 140px;
                padding: 15px;
            }
            
            .stepper {
                gap: 20px;
            }
            
            .stepper-line {
                left: 50px;
                right: 50px;
            }
            
            .card {
                padding: 20px 15px;
            }
            
            .product-table {
                display: block;
                overflow-x: auto;
            }
            
            .header-top {
                flex-direction: column;
                gap: 12px;
            }
            
            .commercial-nav {
                display: none;
                flex-direction: column;
                width: 100%;
                gap: 15px;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .product-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .quantity-control {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="commercial-header">
        <div class="commercial-header-container">
            <div class="header-top">
                <div class="commercial-logo">Salud<span>Perfecta</span></div> 
                
                <nav class="commercial-nav" id="commercialNav">
                    <a href="index.php" class="commercial-nav-item">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                    <a href="index.php#productos" class="commercial-nav-item">
                        <i class="fas fa-cube"></i> Productos
                    </a>
                    <a href="index.php#categorias" class="commercial-nav-item">
                        <i class="fas fa-layer-group"></i> Categorías
                    </a>
                    <a href="carrito.php" class="commercial-nav-item">
                        <i class="fas fa-shopping-bag"></i> Carrito
                    </a>
                </nav>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- STEPPER -->
    <div class="stepper">
        <div class="stepper-line"></div>
        <div class="step active" data-step="0">
            <div class="step-number">1</div>
            <div class="step-label">Resumen</div>
        </div>
        <div class="step" data-step="1">
            <div class="step-number">2</div>
            <div class="step-label">Iniciar Sesión</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-number">3</div>
            <div class="step-label">Datos Personales</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-number">4</div>
            <div class="step-label">Dirección</div>
        </div>
        <div class="step" data-step="4">
            <div class="step-number">5</div>
            
            <div class="step-label">Pago</div>
        </div>
    </div>

    <div class="container">
        <!-- CONTENIDO PRINCIPAL -->
        <main>
            <!-- PASO 1: RESUMEN DEL PEDIDO -->
            <section class="card step-section" id="step-0">
                <div class="card-header">
                    <h2 class="card-title">Resumen de tu Pedido</h2>
                    <div class="step-indicator">Paso 1 de 5</div>
                </div>
                
                <?php if (!empty($_SESSION['carrito'])): ?>
                    <form method="POST" action="carrito.php">
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['carrito'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <?php 
                                                $imagen = isset($productosConInfo[$item['id']]) ? $productosConInfo[$item['id']]['imagen'] : '';
                                                if (!empty($imagen)): 
                                                ?>
                                                    <img src="<?= htmlspecialchars($imagen) ?>" 
                                                         alt="<?= htmlspecialchars($item['nombre']) ?>" 
                                                         class="product-image"
                                                         onerror="this.src='https://via.placeholder.com/80x80/DDD/FFF?text=IMG'">
                                                <?php else: ?>
                                                    <img src="https://via.placeholder.com/80x80/DDD/FFF?text=IMG" 
                                                         alt="Imagen no disponible" 
                                                         class="product-image">
                                                <?php endif; ?>
                                                <div class="product-details">
                                                    <h4><?= htmlspecialchars($item['nombre']) ?></h4>
                                                    <p>Código: <?= $item['id'] ?></p>
                                                    <?php 
                                                    // Mostrar indicador de envío gratis si el producto lo tiene
                                                    if (isset($productosConInfo[$item['id']]) && $productosConInfo[$item['id']]['envio_gratis']): 
                                                    ?>
                                                        <p class=\"envio-gratis-indicator\" style=\"color: #007bff; font-weight: bold; margin: 5px 0;\">
                                                            <i class=\"fas fa-truck\"></i> ¡Envío Gratis!
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (isset($item['precio_regular']) && $item['precio_regular'] > $item['precio']): ?>
                                                        <p class="text-danger">
                                                            <small>
                                                                <s>Precio regular: S/ <?= number_format($item['precio_regular'], 2) ?></s>
                                                                <br>
                                                                <strong id="savings-<?= $item['id'] ?>">Ahorras: S/ <?= number_format(($item['precio_regular'] - $item['precio']) * $item['cantidad'], 2) ?></strong>
                                                            </small>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (isset($item['precio_regular']) && $item['precio_regular'] > $item['precio']): ?>
                                                <div>
                                                    <s class="text-danger">S/ <?= number_format($item['precio_regular'], 2) ?></s>
                                                    <br>
                                                    <strong class="text-success">S/ <?= number_format($item['precio'], 2) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                S/ <?= number_format($item['precio'], 2) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="quantity-control">
                                                <input type="number" name="cantidad[<?= $item['id'] ?>]" 
                                                       value="<?= $item['cantidad'] ?>" min="1" 
                                                       class="quantity-input" 
                                                       data-id="<?= $item['id'] ?>" 
                                                       data-price="<?= $item['precio'] ?>" 
                                                       data-regular-price="<?= $item['precio_regular'] ?? $item['precio'] ?>"
                                                       id="qty-<?= $item['id'] ?>">
                                            </div>
                                        </td>
                                        <td id="subtotal-<?= $item['id'] ?>">S/ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></td>
                                        <td>
                                            <div class="actions">
                                                <button type="button" class="btn-small btn-danger" 
                                                        onclick="removeProduct(<?= $item['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 25px; flex-wrap: wrap; gap: 10px;">
                            <button type="button" class="btn-secondary" onclick="window.location.href='index.php'">
                                ← Seguir comprando
                            </button>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="actualizar" class="btn-warning btn-small">
                                    <i class="fas fa-sync-alt"></i> Actualizar
                                </button>
                                <button type="button" class="btn-primary" onclick="nextToStep(1)">
                                    Continuar →
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Tu carrito está vacío</h3>
                        <p>Agrega algunos productos para continuar con tu compra</p>
                        <button type="button" class="btn-primary" onclick="window.location.href='index.php#productos'" style="padding: 12px 30px;">
                        <i class="fas fa-shopping-bag"></i> Ir de compras
                        </button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- PASO 2: INICIAR SESIÓN -->
            <section class="card step-section" id="step-1">
                <div class="card-header">
                    <h2 class="card-title">Iniciar Sesión</h2>
                    <div class="step-indicator">Paso 2 de 5</div>
                </div>
                
                <p style="margin-bottom: 20px; font-size: 16px;">Ingresa tu correo electrónico para continuar con tu compra</p>
                
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-input" id="email-input" placeholder="tucorreo@gmail.com" required>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 25px; flex-wrap: wrap; gap: 10px;">
                    <button class="btn-secondary" onclick="nextToStep(0)">← Volver al carrito</button>
                    <button class="btn-primary" onclick="nextToStep(2)">Continuar →</button>
                </div>
            </section>

            <!-- PASO 3: DATOS PERSONALES -->
            <section class="card step-section" id="step-2">
                <div class="card-header">
                    <h2 class="card-title">Tus datos personales</h2>
                    <div class="step-indicator">Paso 3 de 5</div>
                </div>
                
                <p style="margin-bottom: 20px; font-size: 16px;">Solicitamos únicamente la información esencial para la finalización de la compra.</p>
                
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-input" id="email-confirm" placeholder="tucorreo@gmail.com" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-input" id="first-name" placeholder="Nombre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellidos</label>
                        <input type="text" class="form-input" id="last-name" placeholder="Apellidos" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipo de documento</label>
                        <select class="form-input" id="doc-type" required>
                            <option value="">Seleccionar</option>
                            <option value="DNI">DNI</option>
                            <option value="CE">Carnet de Extranjería</option>
                            <option value="PASAPORTE">Pasaporte</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número de documento</label>
                        <input type="number" class="form-input" id="doc-number" placeholder="Número de documento" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teléfono / Celular</label>
                    <input type="number" class="form-input" id="phone" placeholder="Número de teléfono" required>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 25px; flex-wrap: wrap; gap: 10px;">
                    <button class="btn-secondary" onclick="nextToStep(1)">← Volver</button>
                    <button class="btn-primary" onclick="nextToStep(3)">Continuar →</button>
                </div>
            </section>

            <!-- PASO 4: DIRECCIÓN -->
            <section class="card step-section" id="step-3">
                <div class="card-header">
                    <h2 class="card-title">Dirección de envío</h2>
                    <div class="step-indicator">Paso 4 de 5</div>
                </div>
                
                <p style="margin-bottom: 20px; font-size: 16px;">Ingresa la dirección donde deseas recibir tu pedido</p>
                
                <div class="form-group">
                    <label class="form-label">Departamento</label>
                    <select class="form-input" id="department" required onchange="cargarProvincias()">
                        <option value="">Seleccionar departamento</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Provincia</label>
                    <select class="form-input" id="province" required onchange="cargarDistritos()">
                        <option value="">Seleccionar provincia</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Distrito</label>
                    <select class="form-input" id="district" required>
                        <option value="">Seleccionar distrito</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-input" id="address" placeholder="Calle, número, piso, departamento" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Detalles adicionales de la dirección (opcional)</label>
                    <input type="text" class="form-input" id="detalles-direccion" placeholder="Referencias, color de casa, etc.">
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 25px; flex-wrap: wrap; gap: 10px;">
                    <button class="btn-secondary" onclick="nextToStep(2)">← Volver</button>
                    <button class="btn-primary" onclick="nextToStep(4)">Continuar →</button>
                </div>
            </section>

            <!-- PASO 5: PAGO -->
            <section class="card step-section" id="step-4">
                <div class="card-header">
                    <h2 class="card-title">Método de pago</h2>
                    <div class="step-indicator">Paso 5 de 5</div>
                </div>
                
                <p style="margin-bottom: 20px; font-size: 16px;">Selecciona tu método de pago preferido</p>
                
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectPayment(this)" data-value="Tarjeta de Crédito">
                        <div class="payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="payment-name">Tarjeta Crédito</div>
                    </div>
                    <div class="payment-method" onclick="selectPayment(this)" data-value="Tarjeta de Débito">
                        <div class="payment-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="payment-name">Tarjeta Débito</div>
                    </div>
                    <div class="payment-method" onclick="selectPayment(this)" data-value="Pago Efectivo">
                        <div class="payment-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="payment-name">Pago Efectivo</div>
                    </div>
                    <div class="payment-method" onclick="selectPayment(this)" data-value="Yape/Plin">
                        <div class="payment-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="payment-name">Yape / Plin</div>
                    </div>
                </div>
                
                <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <p style="margin: 0; font-size: 14px; color: #666;">
                        <i class="fas fa-info-circle"></i> 
                        Selecciona un método de pago para continuar
                    </p>
                </div>

                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loading-spinner">
                    <div class="spinner"></div>
                    <p>Procesando tu pedido...</p>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 25px; flex-wrap: wrap; gap: 10px;">
                    <button class="btn-secondary" onclick="nextToStep(3)">← Volver</button>
                    <button class="btn-primary" id="finalizar-btn" onclick="finalizarCompra()">Finalizar Compra</button>
                </div>
            </section>

            <!-- FORMULARIO OCULTO PARA ENVIAR DATOS A PROCESAR_PEDIDO.PHP -->
            <form id="checkout-form" method="POST" action="procesar_pedido.php" style="display: none;">
                <input type="hidden" name="tipo_documento" id="form-tipo_documento">
                <input type="hidden" name="nro_documento" id="form-nro_documento">
                <input type="hidden" name="nombres" id="form-nombres">
                <input type="hidden" name="apellidos" id="form-apellidos">
                <input type="hidden" name="email" id="form-email">
                <input type="hidden" name="telefono" id="form-telefono">
                <input type="hidden" name="direccion" id="form-direccion">
                <input type="hidden" name="departamento" id="form-departamento">
                <input type="hidden" name="provincia" id="form-provincia">
                <input type="hidden" name="distrito" id="form-distrito">
                <input type="hidden" name="detalles_direccion" id="form-detalles_direccion">
                <input type="hidden" name="metodo_pago" id="form-metodo_pago">
                <input type="hidden" name="tipo_entrega" id="form-tipo_entrega" value="express">
                <input type="hidden" name="monto_descuento" id="form-monto_descuento" value="<?= $descuento_total ?>">
            </form>
        </main>

        <!-- SIDEBAR RESUMEN -->
        <aside class="sidebar">
            <div class="summary-card">
                <h3 class="summary-title">Resumen de compra</h3>
                
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <span>S/ <?= number_format($total + $descuento_total, 2) ?></span>
                </div>
                
                <!-- Línea de descuento -->
                <?php if ($descuento_total > 0): ?>
                <div class="summary-line text-success">
                    <span>Descuento productos:</span>
                    <span>-S/ <?= number_format($descuento_total, 2) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-line">
                    <span>Delivery:</span>
                    <span class="<?= $envio == 0 ? 'shipping-free' : 'shipping-cost' ?>">
                        <?php if ($envio == 0): ?>
                            GRATIS
                        <?php else: ?>
                            S/ <?= number_format($envio, 2) ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="summary-total">
                    <span>Total:</span>
                    <span>S/ <?= number_format($gran_total, 2) ?></span>
                </div>
                
                <?php if ($envio > 0): ?>
                <div style="margin-top: 15px; text-align: center; font-size: 14px; color: #666;">
                    <i class="fas fa-truck"></i> 
                    ¡Faltan S/ <?= number_format(100 - $total, 2) ?> para envío GRATIS!
                </div>
                <?php else: ?>
                <div style="margin-top: 15px; text-align: center; font-size: 14px; color: #28a745;">
                    <i class="fas fa-check-circle"></i> 
                    ¡Felicidades! Tienes Delivery GRATIS
                </div>
                <?php endif; ?>
                
                <button class="btn-primary" id="continuar-btn" onclick="nextToStep(1)" <?= empty($_SESSION['carrito']) ? 'disabled' : '' ?> style="width: 100%;">
                    <?= empty($_SESSION['carrito']) ? 'Carrito Vacío' : 'Continuar con la compra' ?>
                </button>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="index.php" style="color: #666; font-size: 14px;">
                        ← Seguir comprando
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <script>
        // Datos de departamentos, provincias y distritos del Perú
        const ubicacionesPeru = {
            "Amazonas": {
                "Chachapoyas": ["Asunción", "Balsas", "Chachapoyas", "Cheto", "Chiliquin", "Chuquibamba", "Granada", "Huancas", "La Jalca", "Leimebamba", "Levanto", "Magdalena", "Mariscal Castilla", "Molinopampa", "Montevideo", "Olleros", "Quinjalca", "San Francisco de Daguas", "San Isidro de Maino", "Soloco", "Sonche"],
                "Bagua": ["Aramango", "Bagua", "Copallin", "El Parco", "Imaza", "La Peca"],
                "Bongará": ["Cajaruro", "Cumba", "El Milagro", "Jazan", "Jeep", "Lamud", "Pitu", "Yambrasbamba"],
                "Condorcanqui": ["El Cenepa", "Nieva", "Río Santiago"],
                "Luya": ["Camporredondo", "Cocabamba", "Kosñipata", "Lamud", "Longuita", "Lonya Chico", "Luya", "Luya Viejo", "Maria", "Ocalli", "Ocumal", "Pisqui", "Providencia", "San Cristóbal", "San Francisco del Yeso", "San Jerónimo", "San Juan de Lopecancha", "Santa Catalina", "Santo Tomas", "Tingo"],
                "Rodríguez de Mendoza": ["Chirimoto", "Cochamal", "Huambo", "Luya Viejo", "María", "Mendoza", "Milpuc", "Omia", "San Nicolás", "Santa Rosa", "Totora", "Vista Alegre"],
                "Utcubamba": ["Andabamba", "Cajaruro", "Cumba", "El Milagro", "Jazan", "Jeep", "Lamud", "Pitu", "Yambrasbamba"]
            },
            "Ancash": {
                "Huaraz": ["Cochabamba", "Colcabamba", "Huanchay", "Independencia", "Jangas", "La Libertad", "Olleros", "Pampas", "Pariacoto", "Pira", "Tarica", "Huaraz"],
                "Aija": ["Aija", "Coris", "Huacllan", "La Merced", "Succha"],
                "Antonio Raymondi": ["Aczo", "Cajacay", "Chaman", "Cochas", "Huayllacayan", "Huayrasbamba", "Lacabamba", "Llapo", "Pallasca", "Pampas", "Santa Rosa", "Tauca"],
                "Asunción": ["Chacas", "Acochaca"],
                "Bolognesi": ["Abelardo Pardo Lezameta", "Antonio Raymondi", "Aquia", "Cajamarca", "Cajatambo", "Carhuaz", "Casma", "Corongo", "Huari", "Huarmey", "Huaylas", "Mariscal Luzuriaga", "Oyon", "Pallasca", "Pomabamba", "Recuay", "Santa", "Sihuas", "Yungay"],
                "Carhuaz": ["Acopampa", "Amashca", "Anta", "Ataquero", "Carhuaz", "Huallanca", "Huata", "Huayllabamba", "Mashcon", "Pariacoto", "San Miguel de Aco", "Tinco", "Yura"],
                "Carlos Fermín Fitzcarrald": ["Aczo", "Cajacay", "Huacllan", "La Merced", "Succha"],
                "Casma": ["Buena Vista Alta", "Casma", "Comandante Noel", "Yautan"],
                "Corongo": ["Aco", "Bambas", "Corongo", "Huayllas", "Matacoto", "Pamapampa", "San Juan de Yscos", "San Marcos", "Santa Rosa", "Tauca"],
                "Huari": ["Anra", "Cajay", "Chavin de Huantar", "Huacachi", "Huacchis", "Huachis", "Huantar", "Masin", "Paucas", "Ponto", "Rahuapampa", "Rapayan", "San Marcos", "San Pedro de Chana", "Uco"],
                "Huarmey": ["Cochapeti", "Huarmey", "Huayan", "Malvas", "Culebras"],
                "Huaylas": ["Caraz", "Huallanca", "Huata", "Huaylas", "Mato", "Pamparomas", "Pueblo Libre", "Santa Cruz", "Santo Toribio", "Yuracmarca"],
                "Mariscal Luzuriaga": ["Catac", "Cotapunco", "Haquira", "Huayllan", "Las Juntas", "Tintay", "Tambobamba", "Pampamarca", "Fiori", "Conchucos", "San Antonio", "Cusca", "Aquija", "Sorquis", "Pitumarca", "San Marcos"],
                "Oyon": ["Cochas", "Chacayan", "Conchucos", "Huacaschuque", "Matacoto", "Oyon", "Pacllon"],
                "Pallasca": ["Baccha", "Chiquian", "Huaraz", "Pariahuanca", "San Miguel de Corpanqui", "Ticllos", "Yanama", "Cascapara", "Mancos", "Parian", "Pashap", "Pecos", "San Pablo", "Tinco", "Yuracmarca"],
                "Pomabamba": ["Huayllan", "Pomabamba", "Quinuabamba", "Pirias"],
                "Recuay": ["Cajanos", "Cusca", "Huayllapampa", "Mashcon", "Pampas Chico", "Pararinn", "Recuay", "Tapacocha", "Ticapampa"],
                "Santa": ["Callejón de Huaylas", "Huata", "Nepeña", "Paccho", "San Miguel", "Siquisqui", "Yungay", "Coishco", "Chimbote", "Nuevo Chimbote", "Independencia", "Samanco", "Santa"],
                "Sihuas": ["Acobamba", "Alfonso Ugarte", "Cashapampa", "Chingalpo", "Huayllabamba", "Quiches", "Ragash", "San Juan", "Sicsibamba", "Acari", "Cotahuasi", "Chuquibamba", "Orcotuna", "Pampamarca", "Puyca", "Salamanca", "Saya", "Tauria", "Tomepampa", "Toro"],
                "Yungay": ["Cascapara", "Mancos", "Pararinn", "Pira", "Ranrahirca", "Shupluy", "Yanama", "Yungay"]
            },
            "Apurímac": {
                "Abancay": ["Abancay", "Chacoche", "Circa", "Curahuasi", "Huanipaca", "Lambrama", "Pichirhua", "San Pedro de Cachora", "Tamburco"],
                "Andahuaylas": ["Andahuaylas", "Andarapa", "Chalhuanca", "Chapimarca", "Cotaruse", "Huancarama", "Huancaray", "Huayana", "José María Arguedas", "Kishuara", "Pacobamba", "Pacucha", "Pampachiri", "Pomacocha", "San Antonio de Cachi", "San Jerónimo", "San Miguel de Chaccrampa", "Santa María de Chicmo", "Talavera", "Tumay Huaraca", "Turpo", "Kaquiabamba"],
                "Antabamba": ["Antabamba", "El Oro", "Huaquirca", "Juan Espinoza Medrano", "Oropesa", "Pachaconas", "Sabaino"],
                "Aymaraes": ["Capaya", "Caraybamba", "Chalhuanca", "Chapimarca", "Colcabamba", "Cotaruse", "Huancarama", "Huancaray", "Huayana", "José María Arguedas", "Kishuara", "Pacobamba", "Pacucha", "Pampachiri", "Pomacocha", "San Antonio de Cachi", "San Jerónimo", "San Miguel de Chaccrampa", "Santa María de Chicmo", "Talavera", "Tumay Huaraca", "Turpo", "Kaquiabamba"],
                "Cotabambas": ["Andarapa", "Chalhuanca", "Chapimarca", "Cotaruse", "Huancarama", "Huancaray", "Huayana", "José María Arguedas", "Kishuara", "Pacobamba", "Pacucha", "Pampachiri", "Pomacocha", "San Antonio de Cachi", "San Jerónimo", "San Miguel de Chaccrampa", "Santa María de Chicmo", "Talavera", "Tumay Huaraca", "Turpo", "Kaquiabamba"],
                "Chincheros": ["Chincheros", "Anco Huallo", "Cocharcas", "El Porvenir", "Inca Urco", "Los Chankas", "Ocobamba", "Pucara", "San Juan de Iscos", "San Juan de Chacña", "Tinco", "Uranmarca"],
                "Grau": ["Chuquibambilla", "Curasco", "Gamarra", "Huayllati", "Micaela Bastidas", "Pataypampa", "Progreso", "San Antonio", "Santa Rosa", "Turpay", "Vilcabamba", "Virundo", "Curahuasi", "Huayllas", "Ticaco"]
            },
            "Arequipa": {
                "Arequipa": ["Arequipa", "Alto Selva Alegre", "Cayma", "Cerro Colorado", "Characato", "Chiguata", "Jacobo Hunter", "La Joya", "Mariano Melgar", "Miraflores", "Mollebaya", "Paucarpata", "Pocsi", "Polobaya", "Quequeña", "Sabandia", "Sachaca", "San Juan de Siguas", "San Juan de Tarucani", "Santa Isabel de Siguas", "Santa Rita de Siguas", "Socabaya", "Tiabaya", "Uchumayo", "Vitor", "Yanahuara", "Yarabamba", "Yura", "José Luis Bustamante y Rivero"],
                "Camaná": ["Camaná", "José María Quimper", "Mariano Nicolás Valcárcel", "Nicolás de Pierola", "Ocoña", "Quilca", "Samuel Pastor"],
                "Caravelí": ["Caravelí", "Acarí", "Atico", "Atiquipa", "Bella Unión", "Cahuacho", "Chala", "Chaparra", "Huanuhuanu", "Jaqui", "Lomas", "Quicacha", "Yauca del Rosario"],
                "Castilla": ["Aplao", "Andagua", "Ayo", "Chachas", "Chilcaymarca", "Choco", "Huancarqui", "Machaguay", "Orcopampa", "Pampacolca", "Tipan", "Uñon", "Uraca", "Viraco", "Yanaquihua"],
                "Caylloma": ["Tuti", "Abercon", "Chivay", "Caylloma", "Coporaque", "Huambo", "Huanca", "Lari", "Lluta", "Maca", "Madrigal", "San Antonio de Chuca", "Sibayo", "Tapay", "Tisco", "Tuti", "Yanque", "Majes"],
                "Condesuyos": ["Chuquibamba", "Andaray", "Cayarani", "Chichas", "Iray", "Río Grande", "Salamanca", "Yanaquihua"],
                "Islay": ["Mollendo", "Cocachacra", "Dean Valdivia", "Islay", "Mejía", "Punta de Bombón"],
                "La Unión": ["Cotahuasi", "Alca", "Charcana", "Huaynacotas", "Pampamarca", "Puyca", "Salamanca", "Saya", "Tauria", "Tomepampa", "Toro"]
            },
            "Ayacucho": {
                "Huamanga": ["Ayacucho", "Acocro", "Acos Vinchos", "Carmen Alto", "Chiara", "Ocros", "Pacaycasa", "Quinua", "San José de Ticllas", "San Juan Bautista", "Santiago de Pischa", "Socos", "Tambillo", "Vinchos", "Jesús Nazareno"],
                "Cangallo": ["Cangallo", "Chuschi", "Los Morochos", "María Parado de Bellido", "Paras", "Totoropampa"],
                "Huanca Sancos": ["Huanca Sancos", "Carapo", "Sacsamarca", "Santiago de Lucanamarca"],
                "Huanta": ["Huanta", "Ayahuanco", "Huamanguilla", "Iguain", "Luricocha", "Santillana", "Sivia", "Llochegua", "Canayre", "Uchuraccay", "Pucacolpa", "Chaca"],
                "La Mar": ["San Miguel", "Anco", "Ayna", "Chilcas", "Chungui", "Luis Carranza", "Santa Rosa", "Tambo"],
                "Lucanas": ["Puquio", "Aucara", "Cabana", "Carmen Salcedo", "Chaviña", "Chipao", "Huac-Huas", "Laramarca", "Leoncio Prado", "Llauta", "Lucanas", "Ocaña", "Otoca", "Saisa", "San Cristóbal", "San Juan", "San Pedro", "San Pedro de Palco", "Sancos", "Santa Ana de Huayca", "Santa Lucia", "Tibillo"],
                "Parinacochas": ["Coracora", "Chumpi", "Coronel Castañeda", "Pacapausa", "Pullo", "Puyusca", "San Francisco de Ravacayco", "Upahuacho"],
                "Páucar del Sara Sara": ["Pausa", "Colta", "Corculla", "Lampa", "Marcabamba", "Oyolo", "Pararca", "San J. de Salinas", "Sara Sara", "Buenos Aires", "Carapo", "Pitca", "Sacsamarca", "Tinco"],
                "Sucre": ["Querobamba", "Belén", "Chalcos", "Chilcayoc", "Huacaña", "Morcolla", "Paico", "San Pedro de Larcay", "San Salvador de Quije", "Santiago de Paucaray", "Soras"],
                "Víctor Fajardo": ["Huancapi", "Alcamenca", "Apongo", "Asquipata", "Canaria", "Cayara", "Colca", "Huamanquiquia", "Huancapi", "Huancaray", "Huayacundo Arma", "Sarhua", "Vilcanchos"],
                "Vilcas Huamán": ["Vilcashuamán", "Accomarca", "Carhuanca", "Concepción", "Huambalpa", "Independencia", "Saurama", "Vischongo"]
            },
            "Cajamarca": {
                "Cajamarca": ["Cajamarca", "Asunción", "Chetilla", "Cospan", "Encañada", "Jesús", "Llacanora", "Los Baños del Inca", "Magdalena", "Matara", "Namora", "San Juan"],
                "Cajabamba": ["Cajabamba", "Cachachi", "Condebamba", "Sitacocha"],
                "Celendín": ["Celendín", "Chumuch", "Cortegana", "Huasmin", "Jorge Chávez", "José Gálvez", "Miguel Iglesias", "Oxamarca", "Pío IX", "Sonia", "Sucre", "Utco", "La Libertad de Pallan"],
                "Chota": ["Chota", "Anguia", "Chadin", "Chalamarca", "Chiguirip", "Chimban", "Choropampa", "Cochabamba", "Huambos", "Lajas", "Llama", "Miracosta", "Paccha", "Pion", "Querocoto", "San Juan de Licupis", "Tacabamba", "Tocmoche", "Chalcos", "Chupan", "Huambos", "Pomabamba", "San Lucas", "Tocmoche", "Choropampa", "Cachachi"],
                "Contumazá": ["Contumazá", "Chilete", "Cupisnique", "Guzmango", "San Benito", "Santa Cruz de Toledo", "Tantarica", "Yonan"],
                "Cutervo": ["Cutervo", "Callayuc", "Choros", "Cujillo", "La Ramada", "Pimpingos", "Querocotillo", "San Andrés de Cutervo", "San Juan de Cutervo", "San Luis de Lucma", "Santa Cruz", "Santo Domingo de la Capilla", "Santo Tomas", "Socota", "Toribio Casanova"],
                "Hualgayoc": ["Bambamarca", "Chugur", "Hualgayoc"],
                "Jaén": ["Jaén", "Bellavista", "Chontaguí", "Colasay", "Huabal", "Las Pirias", "Pomahuaca", "Pucara", "Sallique", "San Felipe", "San José del Alto", "Santa Rosa"],
                "San Ignacio": ["San Ignacio", "Chirinos", "Huarango", "La Coipa", "Namballe", "San José de Lourdes", "Tabaconas"],
                "San Marcos": ["San Marcos", "San Nicolás", "San Pedro de Cutud", "Santa Rosa", "Urpay"],
                "San Miguel": ["San Miguel", "Bolívar", "Calquis", "Catilluc", "El Prado", "La Florida", "Llapa", "Nanchoc", "Niepos", "San Gregorio", "San Silvestre de Cochan", "Tongod", "Unión Agua Blanca"],
                "San Pablo": ["San Pablo", "San Bernardino", "San Lorenzo", "Tumbaden"],
                "Santa Cruz": ["Santa Cruz", "Andabamba", "Catache", "Chancay", "La Esperanza", "Ninabamba", "Pulan", "Saucepampa", "Sexi", "Uticyacu", "Yauyucan"]
            },
            "Callao": {
                "Callao": ["Callao", "Bellavista", "Carmen de la Legua Reynoso", "La Perla", "La Punta", "Ventanilla", "Mi Perú"]
            },
            "Cusco": {
                "Cusco": ["Cusco", "Ccorca", "Poroy", "San Jerónimo", "San Sebastian", "Santiago", "Saylla", "Wanchaq"],
                "Acomayo": ["Acomayo", "Acopia", "Acos", "Armadeño", "Ccarhuayo", "Cusillu", "Omacha", "San Juan de Pucara", "Tamburco", "Tintay Puncu"],
                "Anta": ["Anta", "Ancahuasi", "Cachimayo", "Chinchaypujio", "Huarocondo", "Limatambo", "Mollemarca", "Pucyura", "Zurite"],
                "Calca": ["Calca", "Coya", "Lamay", "Lares", "Pisac", "San Salvador", "Sanco", "Tintay Puncu"],
                "Canas": ["Sicuani", "Checca", "Combapata", "Kunturkanki", "Langui", "Layo", "Pacobamba", "Pucara", "Santiago", "Vilcabamba", "Quñota", "Andagua", "Santa Ana", "Maranura", "Jairi", "Pomacanchi", "Tupac Amaru"],
                "Canchis": ["Sicuani", "Checca", "Combapata", "Kunturkanki", "Langui", "Layo", "Pacobamba", "Pucara", "Santiago", "Vilcabamba", "Quñota", "Andagua", "Santa Ana", "Maranura", "Jairi", "Pomacanchi", "Tupac Amaru"],
                "Chumbivilcas": ["Sangarara", "Cachiyacu", "Mollepampa", "Pitumarca", "San Pedro", "Uyuni", "Llusco", "Chumbivilcas", "Cosñipata"],
                "Espinar": ["Espinar", "Condoroma", "Coporaque", "Ocoruro", "Pallpata", "Pichigua", "Suyckutambo", "Alto Pichigua"],
                "La Convención": ["Santo Tomás", "Ccarhuayco", "Colquemarca", "Huayopata", "Inkawasi", "Kosñipata", "Machupicchu", "Maranura", "Ocobamba", "Quellouno", "Queno", "Sorayacocha", "Urubamba"],
                "Paruro": ["Paruro", "Accha", "Ccapi", "Colcha", "Huanoquite", "Omacha", "Paccaritambo", "Pillpinto", "Yaurisque"],
                "Paucartambo": ["Puerto Inca", "Cunchabu", "Huacar", "Laramarca", "San Salvador", "Tambo", "Tournavista", "Yuracyacu"],
                "Quispicanchi": ["Urcos", "Andahuaylillas", "Camanti", "Ccanche", "Cusipata", "Huaro", "Lucre", "Marcapata", "Ocongate", "Oropesa", "Quiquijana"],
                "Urubamba": ["Urubamba", "Chinchero", "Huayllabamba", "Machupicchu", "Ollantaytambo", "Pisac", "Pumamarca", "Zurite"]
            },
            "Huancavelica": {
                "Huancavelica": ["Huancavelica", "Acobambilla", "Acoria", "Conayca", "Cuenca", "Huachocolpa", "Huayllahuanca", "Izcuchaca", "Laria", "Manta", "Mariscal Cáceres", "Moya", "Nuevo Occoro", "Pilchaca", "Vilca", "Yauli", "Ascensión", "Huando", "Huantar", "Huayllay Grande", "San José de Chupamarca", "San Juan de Yscos", "San Miguel de Mayocc", "Santiago de Chocorvos", "Santo Domingo de Acobamba", "Sauquillo de Ccaco", "Soracca", "Tintay Puncu", "Yauca"],
                "Acobamba": ["Acobamba", "Andabamba", "Chaquicocha", "Huanca Huanca", "Llautos", "Pomacocha", "Rosario"],
                "Angaraes": ["Lircay", "Anchonga", "Callanmarca", "Ccochaccasa", "Chincho", "Congalla", "Huanca", "Huayllay Grande", "Julcamarca", "San Antonio de Antaparco", "Santo Tomas de Pata", "Secclla"],
                "Castrovirreyna": ["Castrovirreyna", "Arma", "Aurahua", "Capillas", "Chupamarca", "Cocas", "Huachos", "Huamatambo", "Mollepampa", "San Juan", "Santa Ana", "Tantara", "Ticrapo"],
                "Churcampa": ["Churcampa", "Anco", "Chinchihuasi", "El Carmen", "La Merced", "Locroja", "Pachamarca", "Pueblo Nuevo", "San Miguel de Mayocc", "San Pedro de Coris", "Pacapausa", "Cosme", "Santiago de Chocorvos", "Santo Domingo de Acobamba", "Sauquillo de Ccaco", "Soracca", "Tintay Puncu", "Yauca"],
                "Huaytará": ["Huaytará", "Ayavi", "Córdova", "Huayacundo Arma", "Laramarca", "Mollecocha", "Pueblo Nuevo", "Querco", "Quito-Arma", "San Antonio de Cusicancha", "San Francisco de Sangayaico", "San Isidro", "Santiago de Chocorvos", "Santo Domingo de Capillas", "Saya", "Soracca"],
                "Tayacaja": ["Pampas", "Acostambo", "Acraquia", "Ahuaycha", "Colcabamba", "Daniel Hernández", "Huachocolpa", "Huantar", "Huaribamba", "Ñahuimpuquio", "Pazos", "Quishuar", "Salcabamba", "Salcabamba", "San Marcos de Rocchac", "Santa Ana de Tusi", "Santiago de Tucuma", "Santo Domingo", "Taraq", "Yauca del Rosario"]
            },
            "Huánuco": {
                "Huánuco": ["Huánuco", "Amarilis", "Chinchao", "Churubamba", "Margos", "Quisqui", "San Francisco de Cayran", "San Pedro de Chaulan", "Santa María del Valle", "Yarumayo", "Pillco Marca", "Yacus", "San Pablo de Pillao"],
                "Ambo": ["Ambo", "Cayna", "Colpas", "Conchamarca", "Huacar", "San Francisco", "San Rafael", "Tomay Kinti"],
                "Dos de Mayo": ["La Unión", "Chuquis", "Marias", "Pachas", "Quivilla", "Ripan", "Shunqui", "Sillapata", "Yanas"],
                "Huacaybamba": ["Huacaybamba", "Canchabamba", "Punchao", "Puncu", "San Miguel", "Santa Rosa", "Yorongos", "Yacus"],
                "Huamalíes": ["Llata", "Arancay", "Chavín de Pariarca", "Gonchán", "Huacllan", "Monzón", "Punchao", "Puños", "Singa", "Tantamayo"],
                "Leoncio Prado": ["Rupa-Rupa", "Daniel Alomía Robles", "Hermílio Valdizan", "José Crespo y Castillo", "Luyando", "Mariano Damaso Beraun", "Pucayacu", "Castillo Grande", "Pueblo Nuevo", "Santo Domingo de Anda"],
                "Marañón": ["Huacrachuco", "Cholon", "San Buenaventura"],
                "Pachitea": ["Panao", "Chaglla", "Molino", "Umari"],
                "Puerto Inca": ["Puerto Inca", "Codo del Pozuzo", "Honoria", "Tournavista", "Yuyapichis"],
                "Lauricocha": ["Jesús", "Baños", "Jivia", "Queropalca", "Rondos", "San Francisco de Asís", "San Miguel de Cauri"],
                "Yarowilca": ["Chavinillo", "Cahuac", "Chacabamba", "Aparicio Pomares", "Jacas Grande", "Obas", "Pampamarca", "Santa Barbara de Carhuaz", "Huachos", "Pueblo Nuevo de Pillao", "San Miguel de Mayocc", "Santa Rosa de Alto Yanajanca"]
            },
            "Ica": {
                "Ica": ["Ica", "La Tinguiña", "Los Aquijes", "Ocucaje", "Pachacutec", "Parcona", "Pueblo Nuevo", "Salas", "San José de Los Molinos", "San Juan Bautista", "Santiago", "Subtanjalla", "Tate", "Yauca del Rosario"],
                "Chincha": ["Chincha Alta", "Alto Laran", "Chavin", "Chincha Baja", "El Carmen", "Grocio Prado", "Pueblo Nuevo", "San Juan de Yanacancha", "San Pedro de Huacarpana"],
                "Nazca": ["Nazca", "Changuillo", "El Ingenio", "Marcona", "Vista Alegre"],
                "Palpa": ["Palpa", "Llipata", "Río Grande", "Santa Cruz", "Tibillo"],
                "Pisco": ["Pisco", "Huancano", "Humay", "Independencia", "Paracas", "San Andrés", "San Clemente", "Tupac Amaru Inca"]
            },
            "Junín": {
                "Huancayo": ["Huancayo", "Carhuacallanga", "Chacapampa", "Chicche", "Chilca", "Chongos Alto", "Chupuro", "Colca", "Cullhuas", "El Tambo", "Huacrapuquio", "Hualhuas", "Huancan", "Huasicancha", "Huayucachi", "Ingenio", "Pariahuanca", "Pilcomayo", "Pucara", "Quichuay", "Quilcas", "San Agustín", "San Jerónimo", "San Juan de Iscos", "San Juan de Jarpa", "San Miguel de Acos", "San Pedro de Chunan", "Santo Domingo de Acobamba", "Sapallanga", "Sicaya", "Viques"],
                "Chanchamayo": ["La Merced", "Chanchamayo", "El Mantaro", "Perene", "Pichanaqui", "San Luis de Shuaro", "San Ramón", "Vitoc"],
                "Chupaca": ["Chupaca", "Ahuac", "Chongos Bajo", "Huachac", "Huamancaca Chico", "San Juan de Iscos", "San Juan de Jarpa", "Tres de Diciembre", "Yanacancha"],
                "Concepción": ["Concepción", "Aco", "Andamarca", "Chambara", "Cochas", "Comas", "Heroínas Toledo", "Manzanares", "Marías", "Matahuanca", "Mito", "Nueve de Julio", "Orcotuna", "San José de Quero", "Santa Rosa de Ocopa", "Santo Domingo de Acobamba", "Yanacancha"],
                "Huancavelica": ["Huancavelica", "Acobambilla", "Acoria", "Conayca", "Cuenca", "Huachocolpa", "Huayllay Grande", "Izcuchaca", "Laria", "Manta", "Mariscal Cáceres", "Moya", "Nuevo Occoro", "Pilchaca", "Vilca", "Yauli", "Ascensión", "Huando", "Huantar", "Huayllay Grande", "San José de Chupamarca", "San Juan de Yscos", "San Miguel de Mayocc", "Santiago de Chocorvos", "Santo Domingo de Acobamba", "Sauquillo de Ccaco", "Soracca", "Tintay Puncu", "Yauca"],
                "Jauja": ["Jauja", "Acolla", "Canchacallanga", "El Mantaro", "Huamali", "Huaquirca", "Huertas", "Janjaillo", "Julcán", "Leonor Ordóñez", "Llocllapampa", "Marco", "Masma", "Masma Chicche", "Matucana", "Molinos", "Monobamba", "Muqui", "Muquiyauyo", "Paca", "Paccha", "Pancan", "Parco", "Pomacancha", "Pucara", "Quichuas", "Quilcas", "Rondos", "San Lorenzo", "San Pedro de Chunan", "Sausa", "Sincos", "Tunan Marca", "Yauli", "Yauyos"],
                "Junín": ["Junín", "Carhuamayo", "Ondores", "Ulcumayo"],
                "Satipo": ["Satipo", "Coviriali", "Llaylla", "Mazamari", "Pampa Hermosa", "Pangoa", "Río Negro", "Río Tambo", "Vizcatan del Ene"],
                "Tarma": ["Tarma", "Acobamba", "Huaricolca", "Huasahuasi", "La Unión", "Palca", "Palcas", "San Pedro de Cajas", "Tapo"],
                "Yauli": ["Yauli", "Chacapalpa", "Huay-Huay", "Junín", "Moro Collo", "Paccha", "Pancana", "Santa Rosa", "Sapallanga", "Sicaya", "Viques"]
            },
            "La Libertad": {
                "Trujillo": ["Trujillo", "El Porvenir", "Florencia de Mora", "Huanchaco", "La Esperanza", "Laredo", "Moche", "Poroto", "Salaverry", "Simbal", "Victor Larco"],
                "Ascope": ["Ascope", "Casa Grande", "Chicama", "Chocope", "Magdalena de Cao", "Paijan", "Rázuri", "Santiago de Cao", "Casa Grande"],
                "Bolívar": ["Bolívar", "Calquis", "Cascas", "Condirco", "Longorza", "Uchumarca", "Ucuncha"],
                "Chepén": ["Chepén", "Pueblo Nuevo", "San José"],
                "Julcán": ["Julcán", "Calquis", "Carabamba", "Huaso", "Paccha", "Querococh", "San Miguel de Corpanqui", "Totos"],
                "Otuzco": ["Otuzco", "Agallpampa", "Charat", "Huaranchal", "La Cuesta", "Mache", "Paranday", "Salpo", "Sinsicap", "Usquil"],
                "Pacasmayo": ["San Pedro de Lloc", "Guadalupe", "Jequetepeque", "Pacasmayo", "San José"],
                "Pataz": ["Tayabamba", "Buldibuyo", "Chillia", "Huancaspata", "Huaylillas", "Huayo", "Ongon", "Parcoy", "Pataz", "Pias", "Santiago de Challas", "Taurija", "Urpay"],
                "Sánchez Carrión": ["Huamachuco", "Chugay", "Cochorco", "Curgos", "Marcabal", "Sanagoran", "Sarin", "Sartimbamba"],
                "Santiago de Chuco": ["Santiago de Chuco", "Angasmarca", "Cachicadan", "Mollebamba", "Mollepata", "Quiruvilca", "Santa Cruz de Chuco", "Sitabamba"],
                "Gran Chimú": ["Cascas", "Lucma", "Marmot", "Sayapullo"],
                "Virú": ["Virú", "Chao", "Guadalupito"]
            },
            "Lambayeque": {
                "Chiclayo": ["Chiclayo", "Chongoyape", "Eten", "Eten Puerto", "José Leonardo Ortiz", "La Victoria", "Lagunas", "Monsefú", "Nueva Arica", "Oyotún", "Picsi", "Pimentel", "Reque", "Santa Rosa", "Saña", "Cayaltí", "Patapo", "Pomalca", "Pucalá", "Tumán"],
                "Ferreñafe": ["Ferreñafe", "Cañaris", "Incahuasi", "Manuel Antonio Mesones Muro", "Pitipo", "Pueblo Nuevo"],
                "Lambayeque": ["Lambayeque", "Chochope", "Illimo", "Jayanca", "Mochumí", "Morrope", "Motupe", "Olmos", "Pacora", "Salas", "San José", "Tucume"]
            },
            "Lima": {
                "Lima": ["Lima", "Ancón", "Ate", "Barranco", "Breña", "Carabayllo", "Chaclacayo", "Chorrillos", "Cieneguilla", "Comas", "El Agustino", "Independencia", "Jesús María", "La Molina", "La Victoria", "Lince", "Los Olivos", "Lurigancho", "Lurín", "Magdalena del Mar", "Miraflores", "Pachacamac", "Pucusana", "Pueblo Libre", "Puente Piedra", "Punta Hermosa", "Punta Negra", "Rímac", "San Bartolo", "San Borja", "San Isidro", "San Juan de Lurigancho", "San Juan de Miraflores", "San Luis", "San Martín de Porres", "San Miguel", "Santa Anita", "Santa María del Mar", "Santa Rosa", "Santiago de Surco", "Surquillo", "Villa El Salvador", "Villa María del Triunfo"],
                "Barranca": ["Barranca", "Paramonga", "Pativilca", "Supe", "Supe Puerto"],
                "Cajatambo": ["Cajatambo", "Copa", "Gorgor", "Huancapon", "Manas"],
                "Canta": ["Canta", "Arahuay", "Huamantanga", "Huaros", "Lachaqui", "San Buenaventura", "Santa Rosa de Quives"],
                "Cañete": ["San Vicente de Cañete", "Asia", "Calango", "Cerro Azul", "Chilca", "Coayllo", "Imperial", "Lunahuana", "Mala", "Nuevo Imperial", "Pacaran", "Quilmana", "San Antonio", "San Luis", "Santa Cruz", "Zúñiga"],
                "Huaral": ["Huaral", "Atavillos Alto", "Atavillos Bajo", "Aucallama", "Chancay", "Ihuari", "Lampian", "Pacaraos", "San Miguel de Acos", "Santa Cruz de Andamarca", "Sumbilca", "Veintisiete de Noviembre"],
                "Huarochirí": ["Matucana", "Antioquia", "Callahuanca", "Carampoma", "Chicla", "Cuenca", "Huachupampa", "Huanza", "Huarochirí", "Lahuaytambo", "Langa", "Laraos", "Mariatana", "Ricardo Palma", "San Andrés de Tupicocha", "San Antonio", "San Bartolomé", "San Damian", "San Juan de Iris", "San Juan de Tantaranche", "San Lorenzo de Quinti", "San Mateo", "San Mateo de Otao", "San Pedro de Casta", "San Pedro de Huancayre", "Sangallaya", "Santa Cruz de Flores", "Santa Eulalia", "Santiago de Anchucaya", "Santiago de Tuna", "Santo Domingo de Los Olleros", "Surco"],
                "Huaura": ["Huacho", "Ambar", "Caleta de Carquín", "Checras", "Hualmay", "Huaura", "Leoncio Prado", "Paccho", "Santa Leonor", "Santa María", "Sayan", "Vegueta"],
                "Oyón": ["Oyón", "Andajes", "Caujul", "Cochamarca", "Navan", "Pachangara"],
                "Yauyos": ["Yauyos", "Alis", "Allauca", "Ayaviri", "Azángaro", "Cacra", "Carania", "Catahuasi", "Chocos", "Cochas", "Colonia", "Hongos", "Huampara", "Huancaya", "Huangascar", "Huantan", "Huañec", "Laraos", "Lincha", "Madean", "Miraflores", "Omas", "Putinza", "Quinches", "Quinocay", "San Joaquín", "San Pedro de Pilas", "Tanta", "Tauripampa", "Tomas", "Tupe", "Viñac", "Vitis"]
            },
            "Loreto": {
                "Maynas": ["Iquitos", "Alto Nanay", "Florencia de Mora", "Indiana", "Las Amazonas", "Mazan", "Napo", "Punchana", "Torres Causana", "San Juan Bautista"],
                "Alto Amazonas": ["Yurimaguas", "Balsapuerto", "Jeberos", "Lagunas", "Santa Cruz", "Teniente Cesar López Rojas"],
                "Loreto": ["Nauta", "Parinari", "Tigre", "Trompeteros", "Urarinas"],
                "Mariscal Ramón Castilla": ["Caballococha", "Pebas", "Yavari", "San Pablo"],
                "Requena": ["Requena", "Alto Tapiche", "Capelo", "Emilio San Martín", "Maquia", "Puinahua", "Saquena", "Soplin", "Tapiche", "Jenaro Herrera", "Yaquerana"],
                "Ucayali": ["Contamana", "Inahuaya", "Padre Márquez", "Pampa Hermosa", "Sarayacu", "Vargas Guerra"],
                "Datem del Marañón": ["Lagunas", "Santa Bárbara", "Herme", "Teniente Cesar López Rojas", "Barcelona", "Cahuapanas", "Manseriche", "Morona", "Pastaza", "Andoas"],
                "Putumayo": ["Putumayo", "Rosa Panduro", "Teniente Manuel Clavero", "Yagasyacu"]
            },
            "Madre de Dios": {
                "Tambopata": ["Tambopata", "Inambari", "Las Piedras", "Laberinto"],
                "Manu": ["Manu", "Fitzcarrald", "Madre de Dios", "Huepetuhe"],
                "Tahuamanu": ["Iñapari", "Iberia", "Tahuamanu"]
            },
            "Moquegua": {
                "Mariscal Nieto": ["Moquegua", "Carumas", "Cuchumbaya", "Samegua", "San Cristóbal", "Torata"],
                "General Sánchez Cerro": ["Omate", "Chojata", "Coalaque", "Ichuña", "La Capilla", "Lloque", "Matalaque", "Puquina", "Quinistaquillas", "Ubinas", "Yunga"],
                "Ilo": ["Ilo", "El Algarrobal", "Pacocha"]
            },
            "Pasco": {
                "Pasco": ["Chaupimarca", "Huachon", "Huariaca", "Huayllay", "Ninacaca", "Pallanchacra", "Paucartambo", "San Francisco de Asís de Yarusyacán", "Simon Bolívar", "Ticlacayan", "Tinyahuarco", "Vicco", "Yanacancha"],
                "Daniel Alcides Carrión": ["Yanahuanca", "Chacayan", "Goyllarisquizga", "Paucar", "San Francisco de Asís de Yarusyacán", "Simon Bolívar", "Ticlacayan", "Tinyahuarco", "Vicco", "Yanacancha"],
                "Oxapampa": ["Oxapampa", "Chontabamba", "Huancabamba", "Palcazu", "Pozuzo", "Puerto Bermúdez", "Villa Rica", "Constitución"],
                "Yanahuanca": ["Yanahuanca", "Chacayan", "Goyllarisquizga", "Paucar", "San Francisco de Asís de Yarusyacán", "Simon Bolívar", "Ticlacayan", "Tinyahuarco", "Vicco", "Yanacancha"],
                "Grau": ["Chacayan", "Goyllarisquizga", "Huayllay Grande", "Pacaycasa", "Pucacaca", "Sallacc", "San Francisco de Asís de Yarusyacán", "Santa Ana", "Ticlacayan", "Vicco", "Yanacancha"]
            },
            "Piura": {
                "Piura": ["Piura", "Castilla", "Catacaos", "Cura Mori", "El Tallan", "La Arena", "La Unión", "Las Lomas", "Tambo Grande", "Veintiseis de Octubre"],
                "Ayabaca": ["Ayabaca", "Frum", "Jilili", "Lagunas", "Montero", "Pacaipampa", "Paimas", "Sapillica", "Sicchez", "Suyo"],
                "Huancabamba": ["Huancabamba", "Canchaque", "El Carmen de la Frontera", "Huarmaca", "Lalaquiz", "San Miguel de El Faique", "Sondor", "Sondorillo"],
                "Morropón": ["Chulucanas", "Buenos Aires", "Chalaco", "La Matanza", "Morropon", "Salitral", "San Juan de Bigote", "Santa Catalina", "Santo Domingo"],
                "Paita": ["Paita", "Amotape", "Arenal", "Colan", "La Huaca", "Tamarindo", "Vichayal"],
                "Sullana": ["Sullana", "Bellavista", "Ignacio Escudero", "Lancones", "Marcavelica", "Miguel Checa", "Pitipo", "Querecotillo"],
                "Talara": ["Pariñas", "El Alto", "La Brea", "Lobitos", "Los Organos", "Mancora"],
                "Sechura": ["Sechura", "Bellavista de la Unión", "Bernal", "Cristo Nos Valga", "Vice", "Rinconada Luya", "San Francisco", "Santa Cruz", "Bellido"],
                "Pña": ["Pña", "El Carmen", "La Unión", "San Francisco", "Santa Rosa", "Tingömarca", "Vice"]
            },
            "Puno": {
                "Puno": ["Puno", "Acora", "Amantani", "Atuncolla", "Capachica", "Chucuito", "Coata", "Huata", "Mañazo", "Paucarcolla", "Pichacani", "Plateria", "San Antonio", "Tiquillaca", "Vilque"],
                "Azángaro": ["Azángaro", "Achaya", "Arapa", "Asillo", "Caminaca", "Chupa", "Joque", "Muñani", "Potoni", "Samanco", "San Anton", "San José", "San Juan de Salinas", "Santiago de Pupuja", "Tirapata"],
                "Carabaya": ["Macusani", "Ajoyani", "Alfonso Ugarte", "Bethania", "Challapata", "Cogullio", "Curahuasi", "El Collao", "Santa Rosa", "San Gaban", "Yanahuaya", "Papakasi", "Cuyocuyo", "Ticaco"],
                "Chucuito": ["Juli", "Desaguadero", "Huacullani", "Kelluyo", "Pisacoma", "Pomata", "Zepita"],
                "El Collao": ["Ilave", "Capazo", "Pilcuyo", "Santa Rosa", "Conduriri", "Achaya", "Asillo", "Caminaca", "Chupa", "Muñani"],
                "Huancané": ["Huancane", "Cojata", "Huatasani", "Inchupalla", "Pusi", "Rosaspata", "Taraco", "Vilque Chico"],
                "Lampa": ["Lampa", "Cabanilla", "Calapuja", "Nicasio", "Ocuviri", "Palca", "Paratia", "Pucara", "Santa Lucia", "Vilavila"],
                "Melgar": ["Ayaviri", "Antauta", "Curpahuasi", "Huancan", "Nuñoa", "Orurillo", "Santa Rosa", "Umachiri"],
                "Moho": ["Moho", "Conima", "Huayrapata", "Tilali"],
                "San Antonio de Putina": ["Putina", "Ananea", "Pedro Vilca Apaza", "Quilcapuncu", "Sina"],
                "San Román": ["Juliaca", "Cabana", "Cabanillas", "Caracoto", "Pomata", "San Miguel", "Yanahuaya", "Chucuito"],
                "Sandia": ["Sandia", "Cuyocuyo", "Limbani", "Patambuco", "Phara", "Quiaca", "San Juan del Oro", "Yanahuaya", "San Pedro de Putina Punco", "Ollachea", "San Juan de Salinas", "San Jose"],
                "Yunguyo": ["Yunguyo", "Anapia", "Copani", "Cuturapi", "Ollaraya", "Pisacoma", "Pomata", "Zepita"]
            },
            "San Martín": {
                "Moyobamba": ["Moyobamba", "Calzada", "Habana", "Jepelacio", "Soritor", "Yantalo"],
                "Bellavista": ["Bellavista", "Alto Biavo", "Bajo Biavo", "Huallaga", "San Pablo", "San Rafael"],
                "El Dorado": ["San José de Sisa", "Agua Blanca", "San Martín", "Santa Rosa", "Shatoja"],
                "Huallaga": ["Saposoa", "Alto Saposoa", "El Eslabón", "Piscoyacu", "Sacanche", "Tingo de Saposoa"],
                "Lamas": ["Lamas", "Alonso de Alvarado", "Barranquita", "Caynarachi", "Cuñumbuqui", "Pinto Recodo", "Rumisapa", "San Roque de Cumbaza", "Shanao", "Tabalosos", "Zapatero"],
                "Mariscal Cáceres": ["Juanjuí", "Campanilla", "Huicungo", "Pachiza", "Pajarillo"],
                "Picota": ["Picota", "Buenos Aires", "Caspisapa", "Pilluana", "Pucacaca", "San Cristóbal", "San Hilarion", "Shacocbamba", "Shanta", "Tahuania", "Yuracyacu"],
                "Rioja": ["Rioja", "Awajun", "Elías Soplin Vargas", "Nueva Cajamarca", "Pardo Miguel", "Posic", "San Fernando", "San Juan de Sallique", "Santa Rosa", "Yorongos", "Yuracyacu"],
                "San Martín": ["Tarapoto", "Alberto Leveau", "Cacatachi", "Chazuta", "Chipurana", "El Porvenir", "Huimbayoc", "Juan Guerra", "La Banda de Shilcayo", "Morales", "Papaplaya", "San Antonio", "Sauce", "Shapaja"],
                "Tocache": ["Tocache", "Nuevo Progreso", "Polvora", "Shunte", "Uchiza"]
            },
            "Tacna": {
                "Tacna": ["Tacna", "Alto de la Alianza", "Calana", "Ciudad Nueva", "Coronel Gregorio Albarracín Lanchipa", "La Yarada", "Pachia", "Palca", "Pocollay", "Sama", "Yukra"],
                "Candarave": ["Candarave", "Cairani", "Camilaca", "Curibaya", "Huanuara", "Quilahuani"],
                "Jorge Basadre": ["Locumba", "Ilabaya", "Ite"],
                "Tarata": ["Tarata", "Chucatamani", "Estique", "Estique-Pampa", "Papari", "Patachi", "Sausal", "Sítacocha", "Tecalitán", "Tarucachi", "Ticaco"]
            },
            "Tumbes": {
                "Tumbes": ["Tumbes", "Corrales", "La Cruz", "Pampas de Hospital", "San Jacinto", "San Juan de la Virgen"],
                "Contralmirante Villar": ["Zorritos", "Casitas", "Canoas de Punta Sal"],
                "Zarumilla": ["Zarumilla", "Aguas Verdes", "Matapalo", "Papayal"]
            },
            "Ucayali": {
                "Coronel Portillo": ["Pucallpa", "Iparia", "Masisea", "Yarinacocha", "Nueva Requena", "Ucayali"],
                "Atalaya": ["Atalaya", "Chazuta", "Pucacaca", "Shilla", "Yurúa", "Villa Respaldo"],
                "Padre Abad": ["Padre Abad", "Irazola", "Curimana", "Neshuya", "Pichari"],
                "Purus": ["Purus", "Purús", "Yurúa"]
            }
        };

        let currentStep = 0;
        let selectedPaymentMethod = null;
        
        function showStep(step) {
            // Ocultar todos los pasos
            document.querySelectorAll('section.card').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Mostrar el paso actual
            const currentStepElement = document.getElementById(`step-${step}`);
            if (currentStepElement) {
                currentStepElement.classList.remove('hidden');
            }
            
            // Actualizar stepper
            document.querySelectorAll('.step').forEach((stepEl, index) => {
                const stepNumber = parseInt(stepEl.dataset.step);
                stepEl.classList.remove('active', 'completed');
                
                if (stepNumber < step) {
                    stepEl.classList.add('completed');
                } else if (stepNumber === step) {
                    stepEl.classList.add('active');
                }
            });
            
            currentStep = step;
            
            // Scroll to top cuando cambia de paso
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function nextToStep(step) {
            if (step === 1 && <?= empty($_SESSION['carrito']) ? 'true' : 'false' ?>) {
                alert('Tu carrito está vacío. Agrega productos antes de continuar.');
                return;
            }
            
            // Validaciones básicas
            if (step === 2 && !validateStep1()) return;
            if (step === 3 && !validateStep2()) return;
            if (step === 4 && !validateStep3()) return;
            
            showStep(step);
        }
        
        function validateStep1() {
            const email = document.getElementById('email-input').value.trim();
            if (!email) {
                alert('Por favor ingresa tu correo electrónico');
                return false;
            }
            if (!isValidEmail(email)) {
                alert('Por favor ingresa un correo electrónico válido');
                return false;
            }
            return true;
        }
        
        function validateStep2() {
            const email = document.getElementById('email-confirm').value.trim();
            const firstName = document.getElementById('first-name').value.trim();
            const lastName = document.getElementById('last-name').value.trim();
            const docType = document.getElementById('doc-type').value;
            const docNumber = document.getElementById('doc-number').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
            if (!email || !firstName || !lastName || !docType || !docNumber || !phone) {
                alert('Por favor completa todos los campos obligatorios');
                return false;
            }
            
            // Validar formato de email
            if (!isValidEmail(email)) {
                alert('Por favor ingresa un correo electrónico válido');
                return false;
            }
            
            // Validar DNI (8 dígitos)
            if (docType === 'DNI' && !/^\d{8}$/.test(docNumber)) {
                alert('El DNI debe tener exactamente 8 dígitos');
                return false;
            }

            
            
            // Validar teléfono (mínimo 9 dígitos)
            const phoneClean = phone.replace(/\D/g, '');
            if (!/^\d{9,}$/.test(phoneClean)) {
                alert('Por favor ingresa un número de teléfono válido (mínimo 9 dígitos)');
                return false;
            }
            
            return true;
        }
        
        function validateStep3() {
            const department = document.getElementById('department').value;
            const province = document.getElementById('province').value;
            const district = document.getElementById('district').value;
            const address = document.getElementById('address').value.trim();
            
            if (!department || department === "") {
                alert('Por favor selecciona un departamento');
                return false;
            }
            
            if (!province || province === "") {
                alert('Por favor selecciona una provincia');
                return false;
            }
            
            if (!district || district === "") {
                alert('Por favor selecciona un distrito');
                return false;
            }
            
            if (!address) {
                alert('Por favor ingresa tu dirección');
                return false;
            }
            
            if (address.length < 10) {
                alert('Por favor ingresa una dirección más específica (mínimo 10 caracteres)');
                return false;
            }
            
            return true;
        }
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        function selectPayment(element) {
            document.querySelectorAll('.payment-method').forEach(method => {
                method.classList.remove('selected');
            });
            element.classList.add('selected');
            selectedPaymentMethod = element.getAttribute('data-value');
        }
        
        function removeProduct(productId) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
                window.location.href = `carrito.php?remove=${productId}`;
            }
        }
        
        function finalizarCompra() {
            if (!selectedPaymentMethod) {
                alert('Por favor selecciona un método de pago');
                return;
            }
            
            // Validar todos los pasos antes de enviar
            if (!validateStep1() || !validateStep2() || !validateStep3()) {
                alert('Por favor completa todos los campos requeridos');
                return;
            }
            
            if (confirm('¿Estás seguro de que quieres finalizar tu compra?')) {
                // Mostrar loading spinner
                document.getElementById('loading-spinner').style.display = 'block';
                document.getElementById('finalizar-btn').disabled = true;
                
                // Recopilar todos los datos del formulario
                const formData = {
                    tipo_documento: document.getElementById('doc-type').value,
                    nro_documento: document.getElementById('doc-number').value.trim(),
                    nombres: document.getElementById('first-name').value.trim(),
                    apellidos: document.getElementById('last-name').value.trim(),
                    email: document.getElementById('email-confirm').value.trim(),
                    telefono: document.getElementById('phone').value.trim(),
                    direccion: document.getElementById('address').value.trim(),
                    departamento: document.getElementById('department').value,
                    provincia: document.getElementById('province').value,
                    distrito: document.getElementById('district').value,
                    detalles_direccion: document.getElementById('detalles-direccion').value.trim(),
                    metodo_pago: selectedPaymentMethod,
                    tipo_entrega: 'express',
                    monto_descuento: <?= $descuento_total ?>
                };
                
                // Llenar el formulario oculto
                Object.keys(formData).forEach(key => {
                    const input = document.getElementById(`form-${key}`);
                    if (input) {
                        input.value = formData[key];
                    }
                });
                
                // Enviar el formulario después de un pequeño delay para mostrar el spinner
                setTimeout(() => {
                    document.getElementById('checkout-form').submit();
                }, 1000);
            }
        }

        // Funciones para cargar departamentos, provincias y distritos
        function cargarDepartamentos() {
            const selectDepartamento = document.getElementById('department');
            selectDepartamento.innerHTML = '<option value="">Seleccionar departamento</option>';
            
            Object.keys(ubicacionesPeru).forEach(departamento => {
                const option = document.createElement('option');
                option.value = departamento;
                option.textContent = departamento;
                selectDepartamento.appendChild(option);
            });
        }

        function cargarProvincias() {
            const selectDepartamento = document.getElementById('department');
            const selectProvincia = document.getElementById('province');
            const selectDistrito = document.getElementById('district');
            
            const departamentoSeleccionado = selectDepartamento.value;
            
            // Limpiar provincias y distritos
            selectProvincia.innerHTML = '<option value="">Seleccionar provincia</option>';
            selectDistrito.innerHTML = '<option value="">Seleccionar distrito</option>';
            
            if (departamentoSeleccionado && ubicacionesPeru[departamentoSeleccionado]) {
                Object.keys(ubicacionesPeru[departamentoSeleccionado]).forEach(provincia => {
                    const option = document.createElement('option');
                    option.value = provincia;
                    option.textContent = provincia;
                    selectProvincia.appendChild(option);
                });
            }
        }

        function cargarDistritos() {
            const selectDepartamento = document.getElementById('department');
            const selectProvincia = document.getElementById('province');
            const selectDistrito = document.getElementById('district');
            
            const departamentoSeleccionado = selectDepartamento.value;
            const provinciaSeleccionada = selectProvincia.value;
            
            // Limpiar distritos
            selectDistrito.innerHTML = '<option value="">Seleccionar distrito</option>';
            
            if (departamentoSeleccionado && provinciaSeleccionada && 
                ubicacionesPeru[departamentoSeleccionado] && 
                ubicacionesPeru[departamentoSeleccionado][provinciaSeleccionada]) {
                
                ubicacionesPeru[departamentoSeleccionado][provinciaSeleccionada].forEach(distrito => {
                    const option = document.createElement('option');
                    option.value = distrito;
                    option.textContent = distrito;
                    selectDistrito.appendChild(option);
                });
            }
        }
        
        // Variable para saber si el usuario está logueado
        const usuarioLogueado = <?php echo json_encode(isset($_SESSION['logueado']) && $_SESSION['logueado'] === true); ?>;
        

        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            showStep(0);
            cargarDepartamentos();
            
            // Copiar email del paso 1 al paso 2
            const emailInput = document.getElementById('email-input');
            const emailConfirm = document.getElementById('email-confirm');
            
            if (emailInput && emailConfirm) {
                emailInput.addEventListener('input', function() {
                    emailConfirm.value = emailInput.value;
                });
            }
            
            // Mobile menu functionality
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const commercialNav = document.getElementById('commercialNav');
            
            if (mobileMenuBtn && commercialNav) {
                mobileMenuBtn.addEventListener('click', () => {
                    const isVisible = commercialNav.style.display === 'flex';
                    commercialNav.style.display = isVisible ? 'none' : 'flex';
                    
                    if (!isVisible) {
                        commercialNav.style.position = 'absolute';
                        commercialNav.style.top = '100%';
                        commercialNav.style.left = '0';
                        commercialNav.style.right = '0';
                        commercialNav.style.background = 'rgba(107, 114, 128, 0.98)';
                        commercialNav.style.backdropFilter = 'blur(10px)';
                        commercialNav.style.padding = '20px';
                        commercialNav.style.flexDirection = 'column';
                        commercialNav.style.gap = '15px';
                        commercialNav.style.zIndex = '1000';
                    }
                });

                // Cerrar menú al hacer clic fuera
                document.addEventListener('click', (e) => {
                    if (!commercialNav.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                        commercialNav.style.display = 'none';
                    }
                });
            }
        });
        
        // Funcionalidad para actualizar cantidades automáticamente
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const id = this.getAttribute('data-id');
                const price = parseFloat(this.getAttribute('data-price'));
                const regularPrice = parseFloat(this.getAttribute('data-regular-price')) || price;
                const quantity = parseInt(this.value);
                
                // Validar cantidad
                if (quantity < 1) {
                    this.value = 1;
                    return;
                }
                
                // Actualizar subtotal del producto
                const newSubtotal = price * quantity;
                document.getElementById(`subtotal-${id}`).textContent = `S/ ${newSubtotal.toFixed(2)}`;
                
                // Actualizar ahorros si hay descuento
                if (regularPrice > price) {
                    const savings = (regularPrice - price) * quantity;
                    const savingsElement = document.querySelector(`#savings-${id}`);
                    if (savingsElement) {
                        savingsElement.textContent = `Ahorras: S/ ${savings.toFixed(2)}`;
                    }
                }
                
                // Actualizar totales generales
                updateTotals();
                
                // Enviar actualización al servidor
                updateQuantityOnServer(id, quantity);
            });
        });
        
        // Función para actualizar los totales generales
        function updateTotals() {
            let subtotal = 0;
            let totalItems = 0;
            
            document.querySelectorAll('.quantity-input').forEach(input => {
                const price = parseFloat(input.getAttribute('data-price'));
                const quantity = parseInt(input.value);
                subtotal += price * quantity;
                totalItems += quantity;
            });
            
            // Calcular descuentos si existen
            let descuento_total = 0;
            <?php if (isset($descuento_total)) echo "descuento_total = $descuento_total;"; ?>;
            
            const envio = subtotal >= 100 ? 0 : 20;
            const gran_total = subtotal + envio;
            
            // Actualizar los totales en el sidebar
            document.querySelector('.summary-line:nth-child(1) span:last-child').textContent = `S/ ${subtotal.toFixed(2)}`;
            
            // Actualizar línea de descuento si existe
            const descuentoElement = document.querySelector('.text-success');
            if (descuentoElement && descuento_total > 0) {
                descuentoElement.textContent = `-S/ ${descuento_total.toFixed(2)}`;
            }
            
            // Actualizar envío
            const envioElement = document.querySelector('.shipping-cost, .shipping-free');
            if (envioElement) {
                if (envio === 0) {
                    envioElement.textContent = 'GRATIS';
                    envioElement.className = 'shipping-free';
                } else {
                    envioElement.textContent = `S/ ${envio.toFixed(2)}`;
                    envioElement.className = 'shipping-cost';
                }
            }
            
            // Actualizar total general
            document.querySelector('.summary-total span:last-child').textContent = `S/ ${gran_total.toFixed(2)}`;
        }
        
        // Función para enviar actualización al servidor
        function updateQuantityOnServer(id, quantity) {
            // Enviar solicitud AJAX para actualizar la cantidad en la sesión
            fetch('ajax_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_producto=${id}&cantidad=${quantity}&accion=update`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Cantidad actualizada en el servidor');
                } else {
                    console.error('Error al actualizar cantidad en el servidor:', data.error);
                }
            })
            .catch(error => {
                console.error('Error de conexión:', error);
            });
        }
    </script>
</body>
</html>