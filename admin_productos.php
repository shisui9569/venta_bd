<?php
session_start();

// VERIFICACIÓN MEJORADA DE LOGIN
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true || !isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Verificar inactividad (30 minutos)
$inactivity_limit = 30 * 60; // 30 minutos en segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

include 'conexion.php';

$mensaje = '';
$mensaje_tipo = 'success';

// Mostrar mensaje de éxito desde parámetro GET
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $mensaje = "Operación realizada correctamente.";
    $mensaje_tipo = 'success';
}

function clean($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

// Función para validar y formatear fecha
function validarFecha($fecha) {
    if (empty($fecha)) {
        return null;
    }
    
    // Si la fecha viene en formato YYYY-MM-DD (HTML5 date input)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }
    
    // Si viene en otro formato, intentar convertir
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return null;
    }
    
    return date('Y-m-d', $timestamp);
}

// ---------- AGREGAR PRODUCTO ----------
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $precio = floatval($_POST['precio'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    // Nuevos campos de descuento
    $precio_regular = floatval($_POST['precio_regular'] ?? $precio);
    $descuento_porcentaje = floatval($_POST['descuento_porcentaje'] ?? 0);
    $descuento_monto = floatval($_POST['descuento_monto'] ?? 0);
    $en_oferta = isset($_POST['en_oferta']) ? 1 : 0;
    
    // Validar y formatear fechas
    $fecha_inicio_oferta = validarFecha($_POST['fecha_inicio_oferta'] ?? '');
    $fecha_fin_oferta = validarFecha($_POST['fecha_fin_oferta'] ?? '');

    // Si hay descuento, calcular precio final
    if ($descuento_porcentaje > 0) {
        $descuento_monto = $precio_regular * ($descuento_porcentaje / 100);
        $precio = $precio_regular - $descuento_monto;
    } elseif ($descuento_monto > 0) {
        $precio = $precio_regular - $descuento_monto;
        $descuento_porcentaje = ($descuento_monto / $precio_regular) * 100;
    }

    $imagenNombre = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (in_array($_FILES['imagen']['type'], $allowed)) {
            if (!is_dir('imagenes')) mkdir('imagenes', 0777, true);
            $imagenNombre = 'imagenes/' . time() . '_' . bin2hex(random_bytes(6)) . '_' . basename($_FILES['imagen']['name']);
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $imagenNombre)) {
                $imagenNombre = '';
            }
        }
    }

    // Prevenir envíos duplicados verificando si ya existe un producto con el mismo nombre
    $checkQuery = $conexion->prepare("SELECT id_producto FROM productos WHERE nombre = ?");
    $checkQuery->bind_param("s", $nombre);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();
    
    if ($checkResult->num_rows > 0) {
        $mensaje = "Ya existe un producto con ese nombre.";
        $mensaje_tipo = 'error';
        $checkQuery->close();
    } else {
        $checkQuery->close();
        
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, categoria, precio_venta, precio_regular, descuento_porcentaje, descuento_monto, en_oferta, fecha_inicio_oferta, fecha_fin_oferta, imagen, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssdddddssss", $nombre, $descripcion, $categoria, $precio, $precio_regular, $descuento_porcentaje, $descuento_monto, $en_oferta, $fecha_inicio_oferta, $fecha_fin_oferta, $imagenNombre, $stock);
            if ($stmt->execute()) {
                // Redirigir para limpiar POST y prevenir reenvío
                header("Location: admin_productos.php?success=1");
                exit();
            } else {
                $mensaje = "Error al agregar producto: " . $stmt->error;
                $mensaje_tipo = 'error';
            }
            $stmt->close();
        } else {
            $mensaje = "Error en la consulta de inserción: " . $conexion->error;
            $mensaje_tipo = 'error';
        }
    }
}

// ---------- EDITAR PRODUCTO ----------
if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = intval($_POST['id_producto'] ?? 0);
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $precio = floatval($_POST['precio'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    // Validar que el stock sea un número válido
    if ($stock < 0) {
        $mensaje = "El stock no puede ser negativo.";
        $mensaje_tipo = 'error';
    } elseif ($id <= 0) {
        $mensaje = "ID inválido para editar.";
        $mensaje_tipo = 'error';
    } else {
        // Nuevos campos de descuento
        $precio_regular = floatval($_POST['precio_regular'] ?? $precio);
        $descuento_porcentaje = floatval($_POST['descuento_porcentaje'] ?? 0);
        $descuento_monto = floatval($_POST['descuento_monto'] ?? 0);
        $en_oferta = isset($_POST['en_oferta']) ? 1 : 0;
        
        // Validar y formatear fechas
        $fecha_inicio_oferta = validarFecha($_POST['fecha_inicio_oferta'] ?? '');
        $fecha_fin_oferta = validarFecha($_POST['fecha_fin_oferta'] ?? '');

        // Si hay descuento, calcular precio final
        if ($descuento_porcentaje > 0) {
            $descuento_monto = $precio_regular * ($descuento_porcentaje / 100);
            $precio = $precio_regular - $descuento_monto;
        } elseif ($descuento_monto > 0) {
            $precio = $precio_regular - $descuento_monto;
            $descuento_porcentaje = ($descuento_monto / $precio_regular) * 100;
        }

        $nuevaImagen = null;
        $actualizarImagen = false;
        $error_imagen = false;
        
        // VERIFICAR SI SE SUBIÓ UNA NUEVA IMAGEN
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            $fileType = $_FILES['imagen']['type'];
            
            if (in_array($fileType, $allowed)) {
                if (!is_dir('imagenes')) {
                    mkdir('imagenes', 0777, true);
                }
                
                // Generar nombre único para la imagen
                $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $nombreUnico = time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
                $nuevaImagen = 'imagenes/' . $nombreUnico;
                
                // Mover la imagen
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $nuevaImagen)) {
                    $actualizarImagen = true;
                    
                    // Eliminar imagen anterior si existe
                    $q = $conexion->prepare("SELECT imagen FROM productos WHERE id_producto = ?");
                    if ($q) {
                        $q->bind_param("i", $id);
                        $q->execute();
                        $res = $q->get_result();
                        if ($row = $res->fetch_assoc()) {
                            if (!empty($row['imagen']) && file_exists($row['imagen']) && $row['imagen'] != $nuevaImagen) {
                                @unlink($row['imagen']);
                            }
                        }
                        $q->close();
                    }
                } else {
                    $mensaje = "Error al subir la imagen.";
                    $mensaje_tipo = 'error';
                    $error_imagen = true;
                }
            } else {
                $mensaje = "Tipo de archivo no permitido. Formatos aceptados: JPEG, PNG, WebP, GIF.";
                $mensaje_tipo = 'error';
                $error_imagen = true;
            }
        }

        // Si no hay errores, proceder con la actualización
        if (empty($mensaje) || !$error_imagen) {
            if ($actualizarImagen) {
                // CONSULTA CON IMAGEN - CORREGIDO
                $stmt = $conexion->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria=?, precio_venta=?, precio_regular=?, descuento_porcentaje=?, descuento_monto=?, en_oferta=?, fecha_inicio_oferta=?, fecha_fin_oferta=?, imagen=?, stock=? WHERE id_producto=?");
                if ($stmt) {
                    // CORRECCIÓN: Tipos corregidos - 13 parámetros: s=string, d=double, i=integer
                    $stmt->bind_param("sssdddddssssi", 
                        $nombre, 
                        $descripcion, 
                        $categoria, 
                        $precio, 
                        $precio_regular, 
                        $descuento_porcentaje, 
                        $descuento_monto, 
                        $en_oferta, 
                        $fecha_inicio_oferta, 
                        $fecha_fin_oferta, 
                        $nuevaImagen, 
                        $stock, 
                        $id
                    );
                    
                    if ($stmt->execute()) {
                        // Redirigir para limpiar POST y prevenir reenvío
                        header("Location: admin_productos.php?success=1");
                        exit();
                    } else {
                        $mensaje = "Error al editar con imagen: " . $stmt->error;
                        $mensaje_tipo = 'error';
                        // Si falla, eliminar la nueva imagen subida
                        if ($nuevaImagen && file_exists($nuevaImagen)) {
                            @unlink($nuevaImagen);
                        }
                    }
                    $stmt->close();
                } else {
                    $mensaje = "Error en prepare UPDATE (con imagen): " . $conexion->error;
                    $mensaje_tipo = 'error';
                }
            } else {
                // CONSULTA SIN IMAGEN - CORREGIDO
                $stmt = $conexion->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria=?, precio_venta=?, precio_regular=?, descuento_porcentaje=?, descuento_monto=?, en_oferta=?, fecha_inicio_oferta=?, fecha_fin_oferta=?, stock=? WHERE id_producto=?");
                if ($stmt) {
                    // CORRECCIÓN: Tipos corregidos - 12 parámetros
                    $stmt->bind_param("sssdddddssii", 
                        $nombre, 
                        $descripcion, 
                        $categoria, 
                        $precio, 
                        $precio_regular, 
                        $descuento_porcentaje, 
                        $descuento_monto, 
                        $en_oferta, 
                        $fecha_inicio_oferta, 
                        $fecha_fin_oferta, 
                        $stock, 
                        $id
                    );
                    
                    if ($stmt->execute()) {
                        // Redirigir para limpiar POST y prevenir reenvío
                        header("Location: admin_productos.php?success=1");
                        exit();
                    } else {
                        $mensaje = "Error al editar: " . $stmt->error;
                        $mensaje_tipo = 'error';
                    }
                    $stmt->close();
                } else {
                    $mensaje = "Error en prepare UPDATE: " . $conexion->error;
                    $mensaje_tipo = 'error';
                }
            }
        }
    }
}

// ---------- ELIMINAR PRODUCTO ----------
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($id > 0) {
        $q = $conexion->prepare("SELECT imagen FROM productos WHERE id_producto = ?");
        if ($q) {
            $q->bind_param("i", $id);
            $q->execute();
            $res = $q->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['imagen']) && file_exists($row['imagen'])) @unlink($row['imagen']);
            }
            $q->close();
        }

        $del = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
        if ($del) {
            $del->bind_param("i", $id);
            if ($del->execute()) {
                $mensaje = "Producto eliminado correctamente.";
                $mensaje_tipo = 'success';
            } else {
                $mensaje = "Error al eliminar: " . $del->error;
                $mensaje_tipo = 'error';
            }
            $del->close();
        } else {
            if ($conexion->query("DELETE FROM productos WHERE id_producto = $id")) {
                $mensaje = "Producto eliminado correctamente (fallback).";
                $mensaje_tipo = 'success';
            } else {
                $mensaje = "Error al eliminar (fallback): " . $conexion->error;
                $mensaje_tipo = 'error';
            }
        }
    } else {
        $mensaje = "ID inválido para eliminar.";
        $mensaje_tipo = 'error';
    }
}

// ---------- BULK DELETE ----------
if (isset($_POST['accion']) && $_POST['accion'] === 'bulk_delete' && !empty($_POST['bulk_ids'])) {
    $ids = $_POST['bulk_ids'];
    if (is_array($ids)) {
        $safeIds = array_map('intval', $ids);
        $in = implode(',', $safeIds);
        
        // Verificar si hay productos con ventas asociadas
        $checkVentas = $conexion->query("
            SELECT DISTINCT p.id_producto, p.nombre 
            FROM productos p 
            INNER JOIN detalle_ventas dv ON p.id_producto = dv.id_producto 
            WHERE p.id_producto IN ($in)
        ");
        
        $productosConVentas = [];
        if ($checkVentas && $checkVentas->num_rows > 0) {
            while ($row = $checkVentas->fetch_assoc()) {
                $productosConVentas[] = $row['nombre'] . ' (ID: ' . $row['id_producto'] . ')';
            }
        }
        
        // Si hay productos con ventas, mostrar error
        if (!empty($productosConVentas)) {
            $mensaje = "No se pueden eliminar los siguientes productos porque tienen ventas asociadas: " . 
                      implode(', ', $productosConVentas);
            $mensaje_tipo = 'error';
        } else {
            // Eliminar imágenes primero
            $resImgs = $conexion->query("SELECT imagen FROM productos WHERE id_producto IN ($in)");
            if ($resImgs) {
                while ($r = $resImgs->fetch_assoc()) {
                    if (!empty($r['imagen']) && file_exists($r['imagen'])) {
                        @unlink($r['imagen']);
                    }
                }
            }
            
            // Intentar eliminar productos
            if ($conexion->query("DELETE FROM productos WHERE id_producto IN ($in)")) {
                $mensaje = "Productos seleccionados eliminados correctamente.";
                $mensaje_tipo = 'success';
            } else {
                $mensaje = "Error al eliminar productos seleccionados: " . $conexion->error;
                $mensaje_tipo = 'error';
            }
        }
    }
}

// ---------- EXPORTAR CSV ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=productos_export_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nombre', 'Descripcion', 'Categoria', 'Precio Regular', 'Precio Venta', 'Descuento %', 'Descuento Monto', 'En Oferta', 'Stock', 'Imagen']);

    $res = $conexion->query("SELECT id_producto, nombre, descripcion, categoria, precio_regular, precio_venta, descuento_porcentaje, descuento_monto, en_oferta, stock, imagen FROM productos ORDER BY id_producto DESC");
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['id_producto'],
            $row['nombre'],
            $row['descripcion'],
            $row['categoria'],
            $row['precio_regular'],
            $row['precio_venta'],
            $row['descuento_porcentaje'],
            $row['descuento_monto'],
            $row['en_oferta'] ? 'Sí' : 'No',
            $row['stock'],
            $row['imagen']
        ]);
    }

    fclose($output);
    exit();
}

// CONSULTAS PRINCIPALES
$filter_categoria = $_GET['categoria'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "1=1";
if ($filter_categoria !== '') {
    $where .= " AND categoria = '" . $conexion->real_escape_string($filter_categoria) . "'";
}
if ($search !== '') {
    $s = $conexion->real_escape_string($search);
    $where .= " AND (nombre LIKE '%$s%' OR descripcion LIKE '%$s%')";
}

$totalRes = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE $where");
$totalRow = $totalRes->fetch_assoc();
$totalItems = intval($totalRow['total']);
$totalPages = max(1, ceil($totalItems / $perPage));

$listQuery = "SELECT id_producto, nombre, descripcion, categoria, precio_venta, precio_regular, descuento_porcentaje, descuento_monto, en_oferta, fecha_inicio_oferta, fecha_fin_oferta, stock, imagen FROM productos WHERE $where ORDER BY id_producto DESC LIMIT $perPage OFFSET $offset";
$resultado = $conexion->query($listQuery);

$catStats = [];
$catRes = $conexion->query("SELECT categoria, SUM(stock) as total_stock, COUNT(*) as total_prod FROM productos GROUP BY categoria");
while ($c = $catRes->fetch_assoc()) {
    $catStats[] = $c;
}

function badgeClassForCategory($cat) {
    $map = [
        'SoporteInmunologico' => 'bg-rose-100 text-rose-700',
        'BienestarDiario' => 'bg-green-100 text-green-700',
        'EnvejecimientoSaludable' => 'bg-purple-100 text-purple-700',
        'RendimientoDeportivo' => 'bg-yellow-100 text-yellow-700',
        'EstresEstadoAnimo' => 'bg-blue-100 text-blue-700',
        'SaludCerebral' => 'bg-indigo-100 text-indigo-700',
        'Promociones' => 'bg-red-100 text-red-700',
        'PaquetesSalud' => 'bg-orange-100 text-orange-700',
        'Accesorios' => 'bg-teal-100 text-teal-700',
        'Vitaminas' => 'bg-cyan-100 text-cyan-700',
        'Minerales' => 'bg-amber-100 text-amber-700',
    ];
    return $map[$cat] ?? 'bg-gray-100 text-gray-700';
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Productos • Panel • SaludPerfecta</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    ::-webkit-scrollbar { height: 8px; width: 8px; }
    ::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.4); border-radius: 8px; }
    td[data-label]::before { content: attr(data-label) ": "; font-weight: 600; display: inline-block; width: 100px; color: #4b5563; }
    
    .light-mode {
      --bg-primary: #f8fafc;
      --bg-secondary: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
    }
    
    .dark-mode {
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --text-primary: #f1f5f9;
      --text-secondary: #94a3b8;
      --border-color: #334155;
    }
    
    body {
      background-color: var(--bg-primary);
      color: var(--text-primary);
    }
    
    .bg-custom-white {
      background-color: var(--bg-secondary);
    }
    
    .border-custom {
      border-color: var(--border-color);
    }
    
    .text-custom-primary {
      color: var(--text-primary);
    }
    
    .text-custom-secondary {
      color: var(--text-secondary);
    }

    /* Estilos responsive para el modal */
    @media (max-width: 768px) {
      .modal-responsive {
        margin: 1rem;
        width: calc(100% - 2rem);
        max-height: 90vh;
        overflow-y: auto;
      }
      .modal-grid-responsive {
        grid-template-columns: 1fr !important;
        gap: 0.75rem !important;
      }
      .modal-buttons-responsive {
        flex-direction: column;
        gap: 0.5rem;
      }
      .modal-buttons-responsive button {
        width: 100%;
      }
    }

    @media (max-width: 640px) {
      .table-responsive {
        font-size: 0.875rem;
      }
      .table-responsive th,
      .table-responsive td {
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body class="light-mode">

<div id="toaster" class="fixed top-6 right-6 z-50 space-y-2"></div>

<div class="min-h-screen flex">
  <aside class="w-72 bg-custom-white border-custom border-r p-4 flex flex-col">
    <div class="mb-6 flex items-center gap-3">
      <div class="h-10 w-10 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold shadow">
        SP
      </div>
      <div>
        <h1 class="font-bold text-lg text-custom-primary">SaludPerfecta</h1>
        <p class="text-xs text-custom-secondary">Panel administrador</p>
      </div>
    </div>

    <nav class="flex-1 space-y-1">
      <a href="#" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 text-custom-primary">
        <i class="fa-solid fa-gauge-high w-5"></i>
        <span class="font-medium">Dashboard</span>
      </a>
      <a href="admin_productos.php" class="flex items-center gap-3 p-2 rounded-lg bg-indigo-50 text-indigo-700">
        <i class="fa-solid fa-box w-5"></i>
        <span class="font-medium">Productos</span>
      </a>
      <a href="#" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 text-custom-primary">
        <i class="fa-solid fa-cart-shopping w-5"></i>
        <span class="font-medium">Pedidos</span>
      </a>
      <a href="#" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 text-custom-primary">
        <i class="fa-solid fa-users w-5"></i>
        <span class="font-medium">Clientes</span>
      </a>
      <a href="#" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 text-custom-primary">
        <i class="fa-solid fa-chart-pie w-5"></i>
        <span class="font-medium">Reportes</span>
      </a>
    </nav>

    <div class="mt-4 border-t border-custom pt-4">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
            A
          </div>
          <div>
            <div class="text-sm font-semibold text-custom-primary">Administrador</div>
            <div class="text-xs text-custom-secondary">Usuario activo</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button id="toggleTheme" class="p-2 rounded-md bg-gray-100 text-gray-600" title="Cambiar tema">
            <i class="fa-solid fa-moon" id="dark-icon"></i>
            <i class="fa-solid fa-sun hidden" id="light-icon"></i>
          </button>
          <a href="cerrar_sesion.php" class="p-2 rounded-md bg-red-100 text-red-600 hover:bg-red-200" title="Cerrar Sesión">
            <i class="fa-solid fa-right-from-bracket"></i>
          </a>
        </div>
      </div>
    </div>
  </aside>

  <main class="flex-1 p-6 overflow-y-auto">
    <header class="mb-6">
      <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <h2 class="text-2xl font-bold text-custom-primary">Gestión de Productos</h2>
          <span class="px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-700">
            Productos registrados: <?= number_format($totalItems) ?>
          </span>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full lg:w-auto">
          <form method="GET" class="flex items-center gap-2 w-full sm:w-auto" id="searchForm">
            <input type="hidden" name="categoria" value="<?= htmlspecialchars($filter_categoria) ?>">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nombre o descripción" class="px-4 py-2 rounded-lg border border-custom bg-custom-white text-custom-primary w-full sm:w-64" />
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white w-full sm:w-auto">Buscar</button>
          </form>

          <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
            <a href="?export=csv" class="px-4 py-2 rounded-lg border border-custom bg-custom-white text-custom-primary text-center w-full sm:w-auto">Exportar CSV</a>
            <button id="openAddModal" class="px-4 py-2 rounded-lg bg-green-600 text-white w-full sm:w-auto">+ Nuevo</button>
            <a href="cerrar_sesion.php" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors text-center w-full sm:w-auto">
              <i class="fa-solid fa-right-from-bracket mr-2"></i>Cerrar Sesión
            </a>
          </div>
        </div>
      </div>
    </header>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="bg-custom-white rounded-xl p-5 shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-custom-secondary">Total productos</div>
            <div class="text-3xl font-bold text-custom-primary"><?= number_format($totalItems) ?></div>
          </div>
          <div class="p-3 bg-indigo-100 text-indigo-700 rounded-lg">
            <i class="fa-solid fa-box-open"></i>
          </div>
        </div>
      </div>

      <div class="bg-custom-white rounded-xl p-5 shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-custom-secondary">Productos con stock bajo (&lt;=5)</div>
            <?php
              $lowRes = $conexion->query("SELECT COUNT(*) AS low FROM productos WHERE stock <= 5");
              $low = $lowRes->fetch_assoc();
            ?>
            <div class="text-3xl font-bold text-rose-600"><?= number_format($low['low']) ?></div>
          </div>
          <div class="p-3 bg-rose-100 text-rose-700 rounded-lg">
            <i class="fa-solid fa-exclamation-triangle"></i>
          </div>
        </div>
      </div>

      <div class="bg-custom-white rounded-xl p-5 shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-custom-secondary">Productos en oferta</div>
            <?php
              $ofertasRes = $conexion->query("SELECT COUNT(*) AS ofertas FROM productos WHERE en_oferta = 1 AND (fecha_fin_oferta IS NULL OR fecha_fin_oferta >= CURDATE())");
              $ofertas = $ofertasRes->fetch_assoc();
            ?>
            <div class="text-3xl font-bold text-green-600"><?= number_format($ofertas['ofertas']) ?></div>
          </div>
          <div class="p-3 bg-green-100 text-green-700 rounded-lg">
            <i class="fa-solid fa-tag"></i>
          </div>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="lg:col-span-2 bg-custom-white p-5 rounded-xl shadow">
        <canvas id="catChart" height="120"></canvas>
      </div>

      <aside class="bg-custom-white p-5 rounded-xl shadow">
        <h3 class="font-semibold mb-3 text-custom-primary">Filtros rápidos</h3>
        <form id="filters" method="GET" class="space-y-3">
          <div>
            <label class="block text-sm mb-1 text-custom-primary">Categoría</label>
            <select name="categoria" class="w-full p-2 rounded-lg border border-custom bg-custom-white text-custom-primary">
              <option value="">Todas</option>
              <option value="SoporteInmunologico" <?= $filter_categoria=='SoporteInmunologico'?'selected':'' ?>>Soporte Inmunológico</option>
              <option value="BienestarDiario" <?= $filter_categoria=='BienestarDiario'?'selected':'' ?>>Bienestar Diario</option>
              <option value="EnvejecimientoSaludable" <?= $filter_categoria=='EnvejecimientoSaludable'?'selected':'' ?>>Envejecimiento Saludable</option>
              <option value="RendimientoDeportivo" <?= $filter_categoria=='RendimientoDeportivo'?'selected':'' ?>>Rendimiento Deportivo</option>
              <option value="EstresEstadoAnimo" <?= $filter_categoria=='EstresEstadoAnimo'?'selected':'' ?>>Estrés y Estado de Ánimo</option>
              <option value="SaludCerebral" <?= $filter_categoria=='SaludCerebral'?'selected':'' ?>>Salud Cerebral</option>
              <option value="Promociones" <?= $filter_categoria=='Promociones'?'selected':'' ?>>Promociones</option>
              <option value="PaquetesSalud" <?= $filter_categoria=='PaquetesSalud'?'selected':'' ?>>Paquetes de Salud</option>
              <option value="Accesorios" <?= $filter_categoria=='Accesorios'?'selected':'' ?>>Accesorios</option>
              <option value="Vitaminas" <?= $filter_categoria=='Vitaminas'?'selected':'' ?>>Vitaminas</option>
              <option value="Minerales" <?= $filter_categoria=='Minerales'?'selected':'' ?>>Minerales</option>
            </select>
          </div>

          <div>
            <label class="block text-sm mb-1 text-custom-primary">Solo productos en oferta</label>
            <select name="en_oferta" class="w-full p-2 rounded-lg border border-custom bg-custom-white text-custom-primary">
              <option value="">Todos</option>
              <option value="1" <?= ($_GET['en_oferta'] ?? '')=='1'?'selected':'' ?>>Solo ofertas</option>
            </select>
          </div>

          <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg flex-1">Aplicar</button>
            <a href="admin_productos.php" class="px-4 py-2 border border-custom rounded-lg bg-custom-white text-custom-primary flex-1 text-center">Reset</a>
          </div>
        </form>
      </aside>
    </section>

    <section class="bg-custom-white rounded-xl p-5 shadow mb-6">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-4">
        <div class="flex items-center gap-2">
          <label class="inline-flex items-center gap-2 text-custom-primary">
            <input id="selectAll" type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600" />
            <span class="text-sm">Seleccionar todo</span>
          </label>
          <button id="bulkDeleteBtn" class="px-3 py-1 rounded-lg bg-rose-600 text-white hidden">Eliminar seleccionados</button>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
          <span class="text-sm text-custom-secondary">Página <?= $page ?> de <?= $totalPages ?></span>
          <div class="flex items-center gap-1">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&categoria=<?= urlencode($filter_categoria) ?>&en_oferta=<?= urlencode($_GET['en_oferta'] ?? '') ?>" class="px-3 py-1 border border-custom rounded bg-custom-white text-custom-primary">Anterior</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&categoria=<?= urlencode($filter_categoria) ?>&en_oferta=<?= urlencode($_GET['en_oferta'] ?? '') ?>" class="px-3 py-1 border border-custom rounded bg-custom-white text-custom-primary">Siguiente</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <form id="bulkForm" method="POST">
        <input type="hidden" name="accion" value="bulk_delete" />
        <div class="overflow-x-auto">
          <table class="min-w-full table-responsive">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-3 text-left"></th>
                <th class="p-3 text-left text-custom-primary">#</th>
                <th class="p-3 text-left text-custom-primary">Producto</th>
                <th class="p-3 text-left text-custom-primary">Categoría</th>
                <th class="p-3 text-left text-custom-primary">Precio Regular</th>
                <th class="p-3 text-left text-custom-primary">Descuento</th>
                <th class="p-3 text-left text-custom-primary">Precio Final</th>
                <th class="p-3 text-left text-custom-primary">Stock</th>
                <th class="p-3 text-left text-custom-primary">Imagen</th>
                <th class="p-3 text-left text-custom-primary">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($resultado && $resultado->num_rows>0): ?>
                <?php while ($p = $resultado->fetch_assoc()): ?>
                  <tr class="border-b border-custom hover:bg-gray-50">
                    <td class="p-3">
                      <input type="checkbox" name="bulk_ids[]" value="<?= (int)$p['id_producto'] ?>" class="rowCheckbox h-4 w-4" />
                    </td>
                    <td class="p-3 text-custom-primary"><?= (int)$p['id_producto'] ?></td>
                    <td class="p-3">
                      <div class="font-semibold text-custom-primary"><?= clean($p['nombre']) ?></div>
                      <div class="text-xs text-custom-secondary"><?= substr(clean($p['descripcion']),0,80) ?><?= strlen($p['descripcion'])>80?'...':'' ?></div>
                      <?php if ($p['en_oferta'] && (empty($p['fecha_fin_oferta']) || strtotime($p['fecha_fin_oferta']) >= time())): ?>
                        <span class="inline-block mt-1 px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">
                          <i class="fas fa-tag mr-1"></i>EN OFERTA
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="p-3">
                      <span class="px-2 py-1 rounded-full text-xs <?= badgeClassForCategory($p['categoria']) ?>"><?= clean($p['categoria']) ?></span>
                    </td>
                    <td class="p-3 text-custom-primary">
                      <?php if ($p['precio_regular'] > $p['precio_venta']): ?>
                        <span style="text-decoration: line-through; color: #999;">
                          S/ <?= number_format($p['precio_regular'],2) ?>
                        </span>
                      <?php else: ?>
                        S/ <?= number_format($p['precio_regular'],2) ?>
                      <?php endif; ?>
                    </td>
                    <td class="p-3 text-custom-primary">
                      <?php if ($p['descuento_porcentaje'] > 0): ?>
                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                          -<?= number_format($p['descuento_porcentaje'],1) ?>%
                        </span>
                      <?php elseif ($p['descuento_monto'] > 0): ?>
                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                          -S/ <?= number_format($p['descuento_monto'],2) ?>
                        </span>
                      <?php else: ?>
                        <span class="text-gray-400">Sin desc.</span>
                      <?php endif; ?>
                    </td>
                    <td class="p-3 text-custom-primary font-semibold">
                      S/ <?= number_format($p['precio_venta'],2) ?>
                    </td>
                    <td class="p-3">
                      <?php if ((int)$p['stock'] <= 5): ?>
                        <span class="px-2 py-1 rounded text-sm bg-rose-100 text-rose-700"><?= (int)$p['stock'] ?></span>
                      <?php else: ?>
                        <span class="px-2 py-1 rounded text-sm bg-green-100 text-green-700"><?= (int)$p['stock'] ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="p-3">
                      <?php if (!empty($p['imagen']) && file_exists($p['imagen'])): ?>
                        <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" class="h-14 w-14 object-cover rounded" />
                      <?php else: ?>
                        <div class="h-14 w-14 bg-gray-100 rounded flex items-center justify-center text-gray-400">
                          No image
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="p-3">
                      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <button type="button" class="editBtn px-3 py-1 rounded bg-yellow-500 text-white text-sm w-full sm:w-auto" 
                          data-id="<?= (int)$p['id_producto'] ?>"
                          data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                          data-descripcion="<?= htmlspecialchars($p['descripcion'], ENT_QUOTES) ?>"
                          data-categoria="<?= htmlspecialchars($p['categoria'], ENT_QUOTES) ?>"
                          data-precio="<?= htmlspecialchars($p['precio_venta'], ENT_QUOTES) ?>"
                          data-precio_regular="<?= htmlspecialchars($p['precio_regular'] ?? $p['precio_venta'], ENT_QUOTES) ?>"
                          data-descuento_porcentaje="<?= htmlspecialchars($p['descuento_porcentaje'] ?? 0, ENT_QUOTES) ?>"
                          data-descuento_monto="<?= htmlspecialchars($p['descuento_monto'] ?? 0, ENT_QUOTES) ?>"
                          data-en_oferta="<?= htmlspecialchars($p['en_oferta'] ?? 0, ENT_QUOTES) ?>"
                          data-fecha_inicio_oferta="<?= htmlspecialchars($p['fecha_inicio_oferta'] ?? '', ENT_QUOTES) ?>"
                          data-fecha_fin_oferta="<?= htmlspecialchars($p['fecha_fin_oferta'] ?? '', ENT_QUOTES) ?>"
                          data-stock="<?= (int)$p['stock'] ?>"
                          data-imagen="<?= htmlspecialchars($p['imagen'], ENT_QUOTES) ?>"
                        >
                          Editar
                        </button>

                        <a href="?eliminar=<?= (int)$p['id_producto'] ?>" onclick="return confirm('¿Eliminar este producto?')" class="px-3 py-1 rounded bg-rose-600 text-white text-sm text-center w-full sm:w-auto">Eliminar</a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="10" class="p-4 text-center text-custom-secondary">No hay productos que coincidan.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </form>

      <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="text-sm text-custom-secondary">Mostrando <?= min($perPage, $totalItems - $offset) ?> de <?= $totalItems ?> resultados</div>
        <div class="flex flex-wrap items-center gap-2">
          <?php for ($i=1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&categoria=<?= urlencode($filter_categoria) ?>&en_oferta=<?= urlencode($_GET['en_oferta'] ?? '') ?>" class="px-3 py-1 rounded <?= $i==$page ? 'bg-indigo-600 text-white' : 'border border-custom bg-custom-white text-custom-primary' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      </div>
    </section>

    <footer class="text-xs text-custom-secondary mt-6">
      <div>Panel creado con shisui♠ <?= date('Y') ?></div>
    </footer>
  </main>
</div>

<!-- MODAL: AGREGAR / EDITAR - MEJORADO Y RESPONSIVE -->
<div id="modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 p-4">
  <div class="bg-custom-white rounded-xl w-full max-w-2xl modal-responsive">
    <div class="flex items-center justify-between p-4 border-b border-custom">
      <h3 id="modalTitle" class="text-lg font-semibold text-custom-primary">Nuevo producto</h3>
      <button id="closeModal" class="text-custom-secondary hover:text-custom-primary text-xl"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <form id="modalForm" method="POST" enctype="multipart/form-data" class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">
      <input type="hidden" name="accion" id="formAccion" value="agregar">
      <input type="hidden" name="id_producto" id="formId" value="0">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 modal-grid-responsive">
        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Nombre *</label>
          <input id="formNombre" name="nombre" required class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>
 
        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Categoría *</label>
          <select id="formCategoria" name="categoria" required class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <option value="">Seleccione</option>
            <option value="SoporteInmunologico">Soporte Inmunológico</option>
            <option value="BienestarDiario">Bienestar Diario</option>
            <option value="EnvejecimientoSaludable">Envejecimiento Saludable</option>
            <option value="RendimientoDeportivo">Rendimiento Deportivo</option>
            <option value="EstresEstadoAnimo">Estrés y Estado de Ánimo</option>
            <option value="SaludCerebral">Salud Cerebral</option>
            <option value="Promociones">Promociones</option>
            <option value="PaquetesSalud">Paquetes de Salud</option>
            <option value="Accesorios">Accesorios</option>
            <option value="Vitaminas">Vitaminas</option>
            <option value="Minerales">Minerales</option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Precio Regular (S/)</label>
          <input id="formPrecioRegular" name="precio_regular" step="0.01" type="number" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Descuento (%)</label>
          <input id="formDescuentoPorcentaje" name="descuento_porcentaje" step="0.01" type="number" min="0" max="100" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Descuento Monto (S/)</label>
          <input id="formDescuentoMonto" name="descuento_monto" step="0.01" type="number" min="0" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Precio Final (S/) *</label>
          <input id="formPrecio" name="precio" required step="0.01" type="number" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" readonly />
        </div>

        <div class="flex items-center space-x-2 md:col-span-2">
          <input id="formEnOferta" name="en_oferta" type="checkbox" class="h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500" />
          <label class="text-sm font-medium text-custom-primary">En Oferta</label>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Stock *</label>
          <input id="formStock" name="stock" required type="number" min="0" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 modal-grid-responsive">
        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Fecha Inicio Oferta</label>
          <input id="formFechaInicioOferta" name="fecha_inicio_oferta" type="date" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-custom-primary">Fecha Fin Oferta</label>
          <input id="formFechaFinOferta" name="fecha_fin_oferta" type="date" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium text-custom-primary">Descripción</label>
        <textarea id="formDescripcion" name="descripcion" rows="3" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium text-custom-primary">Imagen</label>
        <input id="formImagen" name="imagen" type="file" accept="image/*" class="w-full p-3 border border-custom rounded-lg bg-custom-white text-custom-primary focus:ring-2 focus:ring-indigo-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
        <div id="imgPreview" class="mt-2"></div>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-3 justify-end pt-4 border-t border-custom modal-buttons-responsive">
        <button type="button" id="modalCancel" class="px-6 py-3 border border-custom rounded-lg bg-custom-white text-custom-primary hover:bg-gray-50 transition-colors w-full sm:w-auto">Cancelar</button>
        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors w-full sm:w-auto" id="modalSubmit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function(){
    const mensaje = <?= json_encode($mensaje) ?>;
    const tipo = <?= json_encode($mensaje_tipo) ?>;
    if (mensaje && mensaje.length>0) {
      showToast(mensaje, tipo);
    }
  })();

  function showToast(text, type='info') {
    const container = document.getElementById('toaster');
    const bg = type === 'success' ? 'bg-emerald-100 text-emerald-800' : (type === 'error' ? 'bg-rose-100 text-rose-800' : 'bg-sky-100 text-sky-800');
    const el = document.createElement('div');
    el.className = `p-3 rounded shadow ${bg}`;
    el.innerText = text;
    container.appendChild(el);
    setTimeout(()=> { el.classList.add('opacity-0'); setTimeout(()=> el.remove(), 400); }, 4000);
  }

  const toggleThemeBtn = document.getElementById('toggleTheme');
  const darkIcon = document.getElementById('dark-icon');
  const lightIcon = document.getElementById('light-icon');
  
  const currentTheme = localStorage.getItem('theme') || 'light';
  
  if (currentTheme === 'dark') {
    document.body.classList.remove('light-mode');
    document.body.classList.add('dark-mode');
    darkIcon.classList.add('hidden');
    lightIcon.classList.remove('hidden');
    toggleThemeBtn.classList.remove('bg-gray-100', 'text-gray-600');
    toggleThemeBtn.classList.add('bg-gray-700', 'text-gray-200');
  } else {
    document.body.classList.remove('dark-mode');
    document.body.classList.add('light-mode');
    darkIcon.classList.remove('hidden');
    lightIcon.classList.add('hidden');
    toggleThemeBtn.classList.remove('bg-gray-700', 'text-gray-200');
    toggleThemeBtn.classList.add('bg-gray-100', 'text-gray-600');
  }
  
  toggleThemeBtn.addEventListener('click', () => {
    if (document.body.classList.contains('light-mode')) {
      document.body.classList.remove('light-mode');
      document.body.classList.add('dark-mode');
      localStorage.setItem('theme', 'dark');
      darkIcon.classList.add('hidden');
      lightIcon.classList.remove('hidden');
      toggleThemeBtn.classList.remove('bg-gray-100', 'text-gray-600');
      toggleThemeBtn.classList.add('bg-gray-700', 'text-gray-200');
    } else {
      document.body.classList.remove('dark-mode');
      document.body.classList.add('light-mode');
      localStorage.setItem('theme', 'light');
      darkIcon.classList.remove('hidden');
      lightIcon.classList.add('hidden');
      toggleThemeBtn.classList.remove('bg-gray-700', 'text-gray-200');
      toggleThemeBtn.classList.add('bg-gray-100', 'text-gray-600');
    }
  });

  const modal = document.getElementById('modal');
  const openAddModal = document.getElementById('openAddModal');
  const closeModal = document.getElementById('closeModal');
  const modalCancel = document.getElementById('modalCancel');
  const formAccion = document.getElementById('formAccion');
  const formId = document.getElementById('formId');
  const modalTitle = document.getElementById('modalTitle');
  const modalForm = document.getElementById('modalForm');
  const imgPreview = document.getElementById('imgPreview');

  // Variable para prevenir envíos duplicados
  let formSubmitting = false;

  openAddModal.addEventListener('click', ()=> {
    formAccion.value = 'agregar';
    formId.value = 0;
    modalTitle.innerText = 'Agregar nuevo producto';
    modalForm.reset();
    imgPreview.innerHTML = '';
    
    // Resetear estado del botón
    const submitBtn = document.getElementById('modalSubmit');
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Guardar';
    formSubmitting = false;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  });

  function modalClose() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
  }

  closeModal.addEventListener('click', ()=> { modalClose(); });
  modalCancel.addEventListener('click', ()=> { modalClose(); });

  // Cerrar modal al hacer click fuera
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modalClose();
    }
  });

  // Cálculo automático de descuentos
  document.getElementById('formPrecioRegular').addEventListener('input', calcularDescuento);
  document.getElementById('formDescuentoPorcentaje').addEventListener('input', calcularDescuento);
  document.getElementById('formDescuentoMonto').addEventListener('input', calcularDescuento);

  function calcularDescuento() {
    const precioRegular = parseFloat(document.getElementById('formPrecioRegular').value) || 0;
    const descuentoPorcentaje = parseFloat(document.getElementById('formDescuentoPorcentaje').value) || 0;
    const descuentoMonto = parseFloat(document.getElementById('formDescuentoMonto').value) || 0;
    
    let precioFinal = precioRegular;
    
    if (descuentoPorcentaje > 0) {
      const montoDescuento = precioRegular * (descuentoPorcentaje / 100);
      precioFinal = precioRegular - montoDescuento;
      document.getElementById('formDescuentoMonto').value = montoDescuento.toFixed(2);
    } else if (descuentoMonto > 0) {
      precioFinal = precioRegular - descuentoMonto;
      const porcentaje = (descuentoMonto / precioRegular) * 100;
      document.getElementById('formDescuentoPorcentaje').value = porcentaje.toFixed(2);
    }
    
    document.getElementById('formPrecio').value = precioFinal.toFixed(2);
  }

  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const nombre = btn.dataset.nombre;
      const descripcion = btn.dataset.descripcion;
      const categoria = btn.dataset.categoria;
      const precio = btn.dataset.precio;
      const precio_regular = btn.dataset.precio_regular;
      const descuento_porcentaje = btn.dataset.descuento_porcentaje;
      const descuento_monto = btn.dataset.descuento_monto;
      const en_oferta = btn.dataset.en_oferta;
      const fecha_inicio_oferta = btn.dataset.fecha_inicio_oferta;
      const fecha_fin_oferta = btn.dataset.fecha_fin_oferta;
      const stock = btn.dataset.stock;
      const imagen = btn.dataset.imagen;

      formAccion.value = 'editar';
      formId.value = id;
      modalTitle.innerText = 'Editar producto #' + id;
      document.getElementById('formNombre').value = nombre;
      document.getElementById('formDescripcion').value = descripcion;
      document.getElementById('formCategoria').value = categoria;
      document.getElementById('formPrecioRegular').value = precio_regular;
      document.getElementById('formDescuentoPorcentaje').value = descuento_porcentaje;
      document.getElementById('formDescuentoMonto').value = descuento_monto;
      document.getElementById('formPrecio').value = precio;
      document.getElementById('formStock').value = stock;
      document.getElementById('formEnOferta').checked = en_oferta == '1';
      document.getElementById('formFechaInicioOferta').value = fecha_inicio_oferta;
      document.getElementById('formFechaFinOferta').value = fecha_fin_oferta;
      
      // Limpiar el input de archivo
      document.getElementById('formImagen').value = '';
      
      // Mostrar imagen actual
      imgPreview.innerHTML = '';
      if (imagen && imagen.length > 0 && imagen !== 'null') {
        const img = document.createElement('img');
        img.src = imagen;
        img.className = 'h-28 w-28 object-cover rounded mt-2 border border-gray-300';
        img.alt = 'Imagen actual del producto';
        imgPreview.appendChild(img);
        
        const currentLabel = document.createElement('div');
        currentLabel.className = 'text-xs text-gray-500 mt-1';
        currentLabel.textContent = 'Imagen actual (dejar vacío para mantener)';
        imgPreview.appendChild(currentLabel);
      } else {
        const noImage = document.createElement('div');
        noImage.className = 'text-sm text-gray-500 mt-2';
        noImage.textContent = 'No hay imagen actual';
        imgPreview.appendChild(noImage);
      }

      // Resetear estado del botón
      const submitBtn = document.getElementById('modalSubmit');
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Guardar';
      formSubmitting = false;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';
    });
  });

  document.getElementById('formImagen').addEventListener('change', function(e){
    imgPreview.innerHTML = '';
    const file = this.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
      showToast('Tipo de archivo no permitido. Use JPEG, PNG, WebP o GIF.', 'error');
      this.value = '';
      return;
    }
    
    // Validar tamaño (máximo 5MB)
    if (file.size > 5 * 1024 * 1024) {
      showToast('La imagen es demasiado grande. Máximo 5MB.', 'error');
      this.value = '';
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(ev){
      const img = document.createElement('img');
      img.src = ev.target.result;
      img.className = 'h-28 w-28 object-cover rounded mt-2 border border-gray-300';
      img.alt = 'Vista previa de la imagen';
      imgPreview.appendChild(img);
      
      // Mostrar nombre del archivo
      const fileName = document.createElement('div');
      fileName.className = 'text-xs text-gray-500 mt-1';
      fileName.textContent = 'Archivo: ' + file.name;
      imgPreview.appendChild(fileName);
    };
    reader.onerror = function() {
      showToast('Error al leer el archivo', 'error');
    };
    reader.readAsDataURL(file);
  });

  const selectAll = document.getElementById('selectAll');
  const rowCheckboxes = document.querySelectorAll('.rowCheckbox');
  const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
  const bulkForm = document.getElementById('bulkForm');

  selectAll && selectAll.addEventListener('change', function() {
    rowCheckboxes.forEach(cb => cb.checked = this.checked);
    toggleBulkBtn();
  });

  rowCheckboxes.forEach(cb => cb.addEventListener('change', toggleBulkBtn));

  function toggleBulkBtn() {
    const any = Array.from(rowCheckboxes).some(cb => cb.checked);
    if (any) {
      bulkDeleteBtn.classList.remove('hidden');
    } else {
      bulkDeleteBtn.classList.add('hidden');
    }
  }

  bulkDeleteBtn && bulkDeleteBtn.addEventListener('click', () => {
    if (confirm('¿Eliminar los productos seleccionados? Esta acción no puede deshacerse.')) {
      bulkForm.submit();
    }
  });

  // Prevenir envíos duplicados del formulario modal
  modalForm && modalForm.addEventListener('submit', function(e) {
    if (formSubmitting) {
      e.preventDefault();
      return false;
    }
    
    const nombre = document.getElementById('formNombre').value.trim();
    const precio = parseFloat(document.getElementById('formPrecio').value);
    const stock = parseInt(document.getElementById('formStock').value);
    
    if (!nombre) {
      e.preventDefault();
      showToast('El nombre es obligatorio','error');
      document.getElementById('formNombre').focus();
      return false;
    }
    if (isNaN(precio) || precio < 0) {
      e.preventDefault();
      showToast('Precio inválido','error');
      document.getElementById('formPrecio').focus();
      return false;
    }
    if (isNaN(stock) || stock < 0) {
      e.preventDefault();
      showToast('Stock inválido','error');
      document.getElementById('formStock').focus();
      return false;
    }
    
    // Si pasa la validación, marcar como enviando
    formSubmitting = true;
    
    // Deshabilitar el botón de enviar para prevenir envíos duplicados
    const submitBtn = document.getElementById('modalSubmit');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Guardando...';
    
    // Mostrar mensaje de carga para operaciones con imágenes
    const imagenInput = document.getElementById('formImagen');
    if (imagenInput.files.length > 0) {
      showToast('Subiendo imagen, por favor espere...', 'info');
    }
    
    // Cerrar el modal después de un breve delay para que el usuario vea que se está procesando
    setTimeout(() => {
        modalClose();
    }, 1000);
  });

  (function(){
    const ctx = document.getElementById('catChart');
    if (!ctx) return;
    const data = {
      labels: <?= json_encode(array_map(fn($x)=>$x['categoria'],$catStats)) ?>,
      datasets: [{
        label: 'Stock total por categoría',
        data: <?= json_encode(array_map(fn($x)=>intval($x['total_stock']), $catStats)) ?>,
        backgroundColor: ['#f97316','#06b6d4','#8b5cf6','#10b981','#ec4899','#eab308','#14b8a6','#f43f5e'],
        borderRadius: 6
      }]
    };
    new Chart(ctx, {
      type: 'bar',
      data: data,
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  })();
</script>

</body>
</html>