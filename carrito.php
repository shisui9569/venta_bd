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

// Obtener imágenes de los productos desde la base de datos
$productosConImagenes = [];
if (!empty($_SESSION['carrito'])) {
    $ids = array_map(fn($item) => $item['id'], $_SESSION['carrito']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $conexion->prepare("SELECT id_producto, imagen FROM productos WHERE id_producto IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $productosConImagenes[$row['id_producto']] = $row['imagen'];
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

$envio = $total >= 2000 ? 0 : 15;
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
                                                $imagen = isset($productosConImagenes[$item['id']]) ? $productosConImagenes[$item['id']] : '';
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
                                                    <?php if (isset($item['precio_regular']) && $item['precio_regular'] > $item['precio']): ?>
                                                        <p class="text-danger">
                                                            <small>
                                                                <s>Precio regular: S/ <?= number_format($item['precio_regular'], 2) ?></s>
                                                                <br>
                                                                <strong>Ahorras: S/ <?= number_format(($item['precio_regular'] - $item['precio']) * $item['cantidad'], 2) ?></strong>
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
                                                       class="quantity-input">
                                            </div>
                                        </td>
                                        <td>S/ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></td>
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
                    <span>Envío:</span>
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
                    ¡Faltan S/ <?= number_format(2000 - $total, 2) ?> para envío GRATIS!
                </div>
                <?php else: ?>
                <div style="margin-top: 15px; text-align: center; font-size: 14px; color: #28a745;">
                    <i class="fas fa-check-circle"></i> 
                    ¡Felicidades! Tienes envío GRATIS
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
            "Lima": {
                "Lima": ["Lima", "Ancón", "Ate", "Barranco", "Breña", "Carabayllo", "Chaclacayo", "Chorrillos", "Cieneguilla", "Comas", "El Agustino", "Independencia", "Jesús María", "La Molina", "La Victoria", "Lince", "Los Olivos", "Lurigancho", "Lurín", "Magdalena", "Miraflores", "Pachacamac", "Pucusana", "Pueblo Libre", "Puente Piedra", "Punta Hermosa", "Punta Negra", "Rímac", "San Bartolo", "San Borja", "San Isidro", "San Juan de Lurigancho", "San Juan de Miraflores", "San Luis", "San Martín", "San Miguel", "Santa Anita", "Santa María", "Santa Rosa", "Santiago", "Surco", "Surquillo", "Villa El Salvador", "Villa María"],
                "Huaura": ["Huacho", "Ambar", "Caleta", "Checras", "Hualmay", "Huaura", "Leoncio Prado", "Paccho", "Santa Leonor", "Santa María", "Sayan", "Vegueta"],
                "Cañete": ["San Vicente", "Asia", "Calango", "Cerro Azul", "Chilca", "Coayllo", "Imperial", "Lunahuana", "Mala", "Nuevo Imperial", "Pacaran", "Quilmana", "San Antonio", "San Luis", "Santa Cruz", "Zúñiga"]
            },
            "Arequipa": {
                "Arequipa": ["Arequipa", "Alto Selva Alegre", "Cayma", "Cerro Colorado", "Characato", "Chiguata", "Jacobo Hunter", "La Joya", "Mariano Melgar", "Miraflores", "Mollebaya", "Paucarpata", "Pocsi", "Polobaya", "Quequeña", "Sabandia", "Sachaca", "San Juan de Siguas", "San Juan de Tarucani", "Santa Isabel", "Santa Rita", "Socabaya", "Tiabaya", "Uchumayo", "Vitor", "Yanahuara", "Yarabamba", "Yura"]
            },
            "Cusco": {
                "Cusco": ["Cusco", "Ccorca", "Poroy", "San Jerónimo", "San Sebastian", "Santiago", "Saylla", "Wanchaq"]
            },
            "La Libertad": {
                "Trujillo": ["Trujillo", "El Porvenir", "Florencia de Mora", "Huanchaco", "La Esperanza", "Laredo", "Moche", "Poroto", "Salaverry", "Simbal", "Victor Larco"]
            },
            "Piura": {
                "Piura": ["Piura", "Castilla", "Catacaos", "Cura Mori", "El Tallan", "La Arena", "La Unión", "Las Lomas", "Tambo Grande"]
            },
            "Lambayeque": {
                "Chiclayo": ["Chiclayo", "Chongoyape", "Eten", "Eten Puerto", "José Leonardo Ortiz", "La Victoria", "Lagunas", "Monsefú", "Nueva Arica", "Oyotún", "Picsi", "Pimentel", "Reque", "Santa Rosa", "Saña", "Cayaltí", "Patapo", "Pomalca", "Pucalá", "Tumán"]
            },
            "Junín": {
                "Huancayo": ["Huancayo", "Carhuacallanga", "Chacapampa", "Chicche", "Chilca", "Chongos Alto", "Chupuro", "Colca", "Cullhuas", "El Tambo", "Huacrapuquio", "Hualhuas", "Huancan", "Huasicancha", "Huayucachi", "Ingenio", "Pariahuanca", "Pilcomayo", "Pucara", "Quichuay", "Quilcas", "San Agustín", "San Jerónimo", "San Pedro", "Santo Domingo", "Sapallanga", "Sicaya", "Viques"]
            },
            "Puno": {
                "Puno": ["Puno", "Acora", "Amantani", "Atuncolla", "Capachica", "Chucuito", "Coata", "Huata", "Mañazo", "Paucarcolla", "Pichacani", "Plateria", "San Antonio", "Tiquillaca", "Vilque"]
            },
            "Ancash": {
                "Huaraz": ["Huaraz", "Cochabamba", "Colcabamba", "Huanchay", "Independencia", "Jangas", "La Libertad", "Olleros", "Pampas", "Pariacoto", "Pira", "Tarica"]
            },
            "Ica": {
                "Ica": ["Ica", "La Tinguiña", "Los Aquijes", "Ocucaje", "Pachacutec", "Parcona", "Pueblo Nuevo", "Salas", "San José", "San Juan Bautista", "Santiago", "Subtanjalla", "Tate", "Yauca"]
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
    </script>
</body>
</html>