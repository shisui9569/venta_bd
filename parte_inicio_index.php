<?php
session_start();
require_once "conexion.php";

// ============================
// FILTROS Y PAGINACIÓN
// ============================
$productosPorPagina = 8;

// Página actual
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

// Filtros de búsqueda - SOLO si no es una actualización de página
$busqueda = "";
$categoria = "";
$orden = "";
$envio_gratis = "";

// Verificar si es una carga inicial (sin parámetros GET) o una búsqueda intencional
if (!empty($_GET) && (isset($_GET['buscar']) || isset($_GET['categoria']) || isset($_GET['orden']) || isset($_GET['envio_gratis']))) {
    $busqueda = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : "";
    $categoria = isset($_GET['categoria']) ? $conexion->real_escape_string($_GET['categoria']) : "";
    $orden = isset($_GET['orden']) ? $_GET['orden'] : "";
    $envio_gratis = isset($_GET['envio_gratis']) ? $conexion->real_escape_string($_GET['envio_gratis']) : "";
}

// Construir WHERE dinámico - SOLO PRODUCTOS CON STOCK DISPONIBLE
$where = "WHERE stock > 0";
if ($busqueda != "") {
    $where .= " AND nombre LIKE '%$busqueda%'";
}
if ($categoria != "") {
    $where .= " AND categoria = '$categoria'";
}
if ($envio_gratis != "") {
    $where .= " AND envio_gratis = " . ($envio_gratis == '1' ? '1' : '0');
}

// Ordenamiento
$orderBy = "ORDER BY id_producto DESC";
if ($orden == "precio_menor") $orderBy = "ORDER BY precio_venta ASC";
if ($orden == "precio_mayor") $orderBy = "ORDER BY precio_venta DESC";
if ($orden == "nuevo") $orderBy = "ORDER BY fecha_creacion DESC";
if ($orden == "popular") $orderBy = "ORDER BY ventas DESC";

// Contar productos disponibles
$totalProductosQuery = $conexion->query("SELECT COUNT(*) AS total FROM productos $where");
$totalProductos = $totalProductosQuery->fetch_assoc()['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// Calcular inicio
$inicio = ($pagina - 1) * $productosPorPagina;

// Verificar si la columna envio_gratis existe, si no, agregarla
$result = $conexion->query("DESCRIBE productos LIKE 'envio_gratis'");
if ($result->num_rows == 0) {
    $conexion->query("ALTER TABLE productos ADD COLUMN envio_gratis BOOLEAN DEFAULT FALSE");
}

// Obtener productos de la página actual (solo disponibles)
$sql = "SELECT id_producto, nombre, descripcion, categoria, precio_venta, precio_regular, descuento_porcentaje, descuento_monto, en_oferta, envio_gratis, stock, imagen FROM productos $where $orderBy LIMIT $inicio, $productosPorPagina";
$resultado = $conexion->query($sql);

// Obtener productos agotados para mostrar en sección separada
$sqlAgotados = "SELECT id_producto, nombre, descripcion, categoria, precio_venta, precio_regular, descuento_porcentaje, descuento_monto, en_oferta, envio_gratis, stock, imagen FROM productos WHERE stock = 0 ORDER BY id_producto DESC LIMIT 4";
$resultadoAgotados = $conexion->query($sqlAgotados);
$totalAgotados = $resultadoAgotados->num_rows;
?>