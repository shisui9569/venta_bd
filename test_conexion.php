<?php
require_once "conexion.php";

// Probando la conexión
$sql = "SELECT COUNT(*) as total FROM productos";
$resultado = $conexion->query($sql);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    echo "Conexión exitosa. Total de productos: " . $fila['total'];
} else {
    echo "Error en la consulta: " . $conexion->error;
}

$conexion->close();
?>