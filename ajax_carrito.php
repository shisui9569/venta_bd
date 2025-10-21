<?php
session_start();
include 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 1);
    
    // Validar entradas
    if ($id_producto <= 0 || $cantidad <= 0) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }
    
    // Obtener información del producto
    $stmt = $conexion->prepare("SELECT id_producto, nombre, precio_venta, precio_regular, stock, imagen FROM productos WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        exit;
    }
    
    $producto = $result->fetch_assoc();
    
    // Verificar stock disponible
    if ($producto['stock'] < $cantidad) {
        echo json_encode([
            'success' => false, 
            'error' => 'No hay suficiente stock disponible', 
            'stock_disponible' => $producto['stock'],
            'cantidad_solicitada' => $cantidad
        ]);
        exit;
    }
    
    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    
    // Buscar si el producto ya está en el carrito
    $producto_encontrado = false;
    $indice_producto = -1;
    
    // Encontrar el índice del producto si ya existe
    foreach ($_SESSION['carrito'] as $indice => $item) {
        if ($item['id'] == $id_producto) {
            $indice_producto = $indice;
            $producto_encontrado = true;
            break;
        }
    }
    
    // Si el producto ya está en el carrito, actualizar cantidad
    if ($producto_encontrado) {
        $cantidad_actual = $_SESSION['carrito'][$indice_producto]['cantidad'];
        $nueva_cantidad = $cantidad_actual + $cantidad;
        
        // Verificar que la nueva cantidad no exceda el stock
        if ($nueva_cantidad > $producto['stock']) {
            echo json_encode([
                'success' => false, 
                'error' => 'No hay suficiente stock disponible. Solo quedan ' . $producto['stock'] . ' unidades disponibles.',
                'stock_disponible' => $producto['stock'],
                'cantidad_en_carrito' => $cantidad_actual,
                'cantidad_a_agregar' => $cantidad,
                'cantidad_maxima_permitida' => $producto['stock'] - $cantidad_actual
            ]);
            exit;
        }
        
        // Actualizar la cantidad en el carrito
        $_SESSION['carrito'][$indice_producto]['cantidad'] = $nueva_cantidad;
    } 
    // Si no está en el carrito, agregarlo como nuevo ítem
    else {
        // Verificar que la cantidad solicitada no exceda el stock
        if ($cantidad > $producto['stock']) {
            echo json_encode([
                'success' => false, 
                'error' => 'No hay suficiente stock disponible. Solo quedan ' . $producto['stock'] . ' unidades disponibles.',
                'stock_disponible' => $producto['stock'],
                'cantidad_solicitada' => $cantidad
            ]);
            exit;
        }
        
        // Agregar nuevo producto al carrito
        $_SESSION['carrito'][] = [
            'id' => $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'precio' => $producto['precio_venta'],
            'precio_regular' => $producto['precio_regular'],
            'cantidad' => $cantidad,
            'imagen' => $producto['imagen']
        ];
    }
    
    // Calcular totales del carrito
    $total_items_carrito = 0;
    $total_cantidad_productos = 0;
    
    foreach ($_SESSION['carrito'] as $item) {
        $total_items_carrito++;
        $total_cantidad_productos += $item['cantidad'];
    }
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado al carrito correctamente',
        'total_items' => $total_items_carrito,
        'total_cantidad' => $total_cantidad_productos,
        'stock_actual' => $producto['stock'],
        'cantidad_agregada' => $cantidad,
        'producto_nombre' => $producto['nombre']
    ]);
    
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}

// Cerrar conexión
$conexion->close();
?>