<?php
include 'conexion.php';

// Intentar agregar la columna de envío gratis si no existe
$sql = "ALTER TABLE productos ADD COLUMN envio_gratis BOOLEAN DEFAULT FALSE";

if ($conexion->query($sql) === TRUE) {
    echo "Columna 'envio_gratis' agregada exitosamente a la tabla productos";
} else {
    // Verificar si la columna ya existe
    $result = $conexion->query("DESCRIBE productos LIKE 'envio_gratis'");
    if ($result->num_rows > 0) {
        echo "La columna 'envio_gratis' ya existe en la tabla productos";
    } else {
        echo "Error al agregar la columna: " . $conexion->error;
    }
}

$conexion->close();
?>