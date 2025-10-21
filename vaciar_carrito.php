<?php
session_start();

// Vaciar todo el carrito
unset($_SESSION['carrito']);

// Redirigir de vuelta al carrito con mensaje de éxito
$_SESSION['mensaje'] = "Carrito vaciado correctamente";
header("Location: carrito.php");
exit();
?>