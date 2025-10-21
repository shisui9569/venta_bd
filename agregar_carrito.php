<?php
session_start();
include 'conexion.php';

if (isset($_GET['id'])) {
    $id_producto = intval($_GET['id']);

    // Obtener datos del producto desde BD
    $consulta = $conexion->prepare("SELECT id_producto, nombre, precio_venta FROM productos WHERE id_producto = ?");
    $consulta->bind_param("i", $id_producto);
    $consulta->execute();
    $resultado = $consulta->get_result();

    if ($producto = $resultado->fetch_assoc()) {
        // Estructura uniforme del carrito
        $item = [
            'id'       => $producto['id_producto'],
            'nombre'   => $producto['nombre'],
            'precio'   => (float)$producto['precio_venta'],
            'cantidad' => 1
        ];

        // Si ya existe en el carrito, aumentar cantidad
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $existe = false;
        foreach ($_SESSION['carrito'] as $key => $prod) {
            if ($prod['id'] == $id_producto) {
                $_SESSION['carrito'][$key]['cantidad']++;
                $existe = true;
                break;
            }
        }

        if (!$existe) {
            $_SESSION['carrito'][] = $item;
        }
    }
}

header("Location: carrito.php");
exit();
