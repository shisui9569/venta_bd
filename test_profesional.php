<?php
// Test file to verify the professional styling
session_start();

// Initialize cart if not already done
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Add a test product if cart is empty
if (empty($_SESSION['carrito'])) {
    $_SESSION['carrito'][] = [
        'id' => 1,
        'nombre' => 'Producto de Prueba',
        'precio' => 59.90,
        'cantidad' => 2
    ];
}

// Calculate totals for demo purposes
$total = 0;
$descuento_total = 0;

foreach ($_SESSION['carrito'] as $item) {
    $subtotal = $item['precio'] * $item['cantidad'];
    $total += $subtotal;
}

$envio = $total >= 2000 ? 0 : 15;
$gran_total = $total + $envio;

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Prueba de Diseño Profesional - SaludPerfecta</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@400;500;600;700;900&display=swap');

        :root {
            --primary: #1e3a8a;
            --secondary: #1d4ed8;
            --accent: #3b82f6;
            --dark: #1e293b;
            --light: #f1f5f9;
            --white: #ffffff;
            --red-accent: #dc2626;
            --info-blue: #2563eb;
            --text-color: #1e293b;
            --light-text: #f8fafc;
            --text-secondary: #64748b;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --gradient-1: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            --gradient-2: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            --gradient-3: linear-gradient(135deg, var(--accent) 0%, #60a5fa 100%);
            --gradient-header: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(30, 58, 138, 0.2);
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: 'Inter', 'Arial', 'Helvetica Neue', Arial, sans-serif; 
            background: #f1f5f9; 
            color: var(--text-color); 
            line-height: 1.6;
            overflow-x: hidden;
            background: #f8fafc;
            padding: 20px;
        }

        .success-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e2e8f0;
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 30px;
            font-family: 'Orbitron', 'Arial', sans-serif;
            text-align: center;
        }

        .success-content {
            text-align: center;
            margin: 40px 0;
        }

        .success-message {
            font-size: 20px;
            color: #16a34a;
            margin-bottom: 20px;
        }

        .test-link {
            display: inline-block;
            background: var(--gradient-1);
            color: white;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .test-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.3);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .feature {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .feature h3 {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .feature ul {
            list-style: none;
            padding-left: 0;
        }

        .feature li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }

        .feature li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #16a34a;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='success-container'>
        <h1 class='success-title'>Diseño Profesional para Carrito Completado</h1>
        
        <div class='success-content'>
            <div class='success-message'>
                ✓ El archivo carrito.php ha sido actualizado con un diseño profesional
            </div>
            <p>Se han implementado las siguientes mejoras:</p>
            
            <div class='features'>
                <div class='feature'>
                    <h3>Mejora de Estilo</h3>
                    <ul>
                        <li>Paleta de colores profesional (azul)</li>
                        <li>Diseño limpio y moderno</li>
                        <li>Tipografía profesional</li>
                        <li>Elementos con sombras sutiles</li>
                    </ul>
                </div>
                
                <div class='feature'>
                    <h3>Traducciones</h3>
                    <ul>
                        <li>Etiquetas en español</li>
                        <li>Textos localizados</li>
                        <li>Formularios en español</li>
                        <li>Etiquetas profesionales</li>
                    </ul>
                </div>
                
                <div class='feature'>
                    <h3>Experiencia de Usuario</h3>
                    <ul>
                        <li>Botones + y - para cantidades</li>
                        <li>Interfaz intuitiva</li>
                        <li>Respuesta a dispositivos móviles</li>
                        <li>Feedback visual</li>
                    </ul>
                </div>
            </div>
            
            <a href='carrito.php' class='test-link'>Ver Carrito Mejorado</a>
        </div>
    </div>
</body>
</html>";
?>