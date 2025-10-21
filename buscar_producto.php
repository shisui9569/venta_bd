<?php
header('Content-Type: application/json');
include 'conexion.php';

if (isset($_POST['producto_nombre'])) {
    $producto_nombre = trim($_POST['producto_nombre']);
    
    // Buscar el producto en la base de datos
    $stmt = $conexion->prepare("SELECT nombre, descripcion, categoria, precio_venta, precio_regular, stock FROM productos WHERE nombre LIKE ? OR descripcion LIKE ?");
    $search_param = "%$producto_nombre%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $productos = [];
        while ($producto = $result->fetch_assoc()) {
            $productos[] = $producto;
        }
        
        echo json_encode([
            'encontrado' => true,
            'productos' => $productos
        ]);
    } else {
        echo json_encode([
            'encontrado' => false,
            'mensaje' => 'No encontramos productos con ese nombre.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'encontrado' => false,
        'mensaje' => 'No se proporcionó un nombre de producto para buscar.'
    ]);
}
?>