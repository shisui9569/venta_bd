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
$result = $conexion->query("SHOW COLUMNS FROM productos LIKE 'envio_gratis'");
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
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SaludPerfecta - Experiencia Futurista</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ============================================
       IMPORTACIÓN DE FUENTES GOOGLE
       ============================================ */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@400;500;600;700;900&display=swap');

    /* ============================================
       RESET Y CONFIGURACIÓN BASE
       ============================================ */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* ============================================
       VARIABLES CSS (Custom Properties)
       ============================================ */
    :root {
      /* Paleta de colores principal */
      --primary: #10b981;
      --secondary: #059669;
      --accent: #34d399;
      --dark: #0f172a;
      --light: #f0fdf4;
      --white: #ffffff;
      --red-accent: #ef4444;
      --info-blue: #3b82f6;
      
      /* Colores de texto */
      --text-color: #374151;
      --light-text: #f9fafb;
      --text-secondary: #6b7280;
      
      /* Fondos */
      --bg-color: #f8fafc;
      --card-bg: #ffffff;
      
      /* Sistema de sombras */
      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      
      /* Gradientes personalizados */
      --gradient-1: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      --gradient-2: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
      --gradient-3: linear-gradient(135deg, var(--accent) 0%, var(--light) 100%);
      --gradient-header: linear-gradient(135deg, #1e293b 0%, #334155 100%);
      
      /* Efectos de vidrio (glassmorphism) */
      --glass-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(16, 185, 129, 0.2);
      
      /* Colores neón para acentos */
      --neon-cyan: #00ffff;
      --neon-purple: #c724b1;
      --neon-pink: #ff2d95;

      /* COLORES PLOMO/GRIS PARA EL HEADER */
      --gris-500: #64748b;
      --gris-600: #475569;
      --gris-700: #334155;
    }

    /* ============================================
       CONFIGURACIÓN DEL BODY Y FONDO
       ============================================ */
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg-color);
      color: var(--text-color);
      overflow-x: hidden;
      position: relative;
      min-height: 100vh;
      font-size: 14px;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(5, 150, 105, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(52, 211, 153, 0.05) 0%, transparent 50%);
      z-index: -2;
      animation: backgroundShift 25s ease-in-out infinite;
    }

    @keyframes backgroundShift {
      0%, 100% { transform: rotate(0deg) scale(1); }
      33% { transform: rotate(120deg) scale(1.1); }
      66% { transform: rotate(240deg) scale(0.9); }
    }

    /* ============================================
       SISTEMA DE PARTÍCULAS FLOTANTES
       ============================================ */
    .particles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: -1;
    }

    .particle {
      position: absolute;
      width: 2px;
      height: 2px;
      background: var(--primary);
      border-radius: 50%;
      box-shadow: 0 0 6px var(--primary);
      animation: float-particle 18s infinite linear;
    }

    @keyframes float-particle {
      0% {
        transform: translateY(100vh) translateX(0);
        opacity: 0;
      }
      10% {
        opacity: 1;
      }
      90% {
        opacity: 1;
      }
      100% {
        transform: translateY(-100vh) translateX(100px);
        opacity: 0;
      }
    }

    /* ============================================
       ENCABEZADO PRINCIPAL (HEADER) - CORREGIDO
       ============================================ */
    .commercial-header {
      background: var(--gradient-header);
      padding: 14px 0;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      transition: all 0.3s ease;
    }

    .commercial-header.scrolled {
      background: rgba(30, 41, 59, 0.98) !important;
      backdrop-filter: blur(12px);
      padding: 10px 0;
    }

    .commercial-header-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 24px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 18px;
    }

    .commercial-logo {
      font-family: 'Orbitron', monospace;
      font-size: 26px;
      font-weight: 700;
      color: white;
      text-transform: uppercase;
      letter-spacing: 1.8px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .commercial-logo span {
      color: #10b981;
      text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
    }

    .commercial-logo i {
      color: #10b981;
      font-size: 24px;
    }

    .commercial-search {
      flex: 1;
      max-width: 500px;
      margin: 0 35px;
      min-width: 220px;
      position: relative;
      z-index: 1;
    }

    .commercial-search-bar {
      position: relative;
      width: 100%;
      z-index: 10;
    }

    .commercial-search-input {
      width: 100%;
      padding: 12px 50px 12px 18px;
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid transparent;
      border-radius: 30px;
      color: #1e293b;
      font-size: 14px;
      transition: all 0.3s;
      font-family: 'Inter', sans-serif;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      box-sizing: border-box;
      position: relative;
      z-index: 10;
    }

    .commercial-search-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
      width: 100% !important;
    }

    .commercial-search-btn {
      position: absolute;
      right: 4px;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s;
      color: var(--light-text);
      font-weight: bold;
      font-size: 14px;
      z-index: 11;
    }

    .commercial-search-btn:hover {
      transform: translateY(-50%) scale(1.05);
      box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
      background: var(--secondary);
    }

    .commercial-nav {
      display: flex;
      gap: 24px;
      align-items: center;
      flex-wrap: wrap;
    }

    .commercial-nav-item {
      color: white;
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
      transition: color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      padding: 8px 12px;
      border-radius: 8px;
      position: relative;
    }

    .commercial-nav-item::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary);
      transition: width 0.3s ease;
    }

    .commercial-nav-item:hover {
      color: var(--primary);
    }

    .commercial-nav-item:hover::after {
      width: 100%;
    }

    .mobile-menu-btn {
      display: none;
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 12px;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: white;
      transition: all 0.3s;
      font-size: 20px;
    }

    .mobile-menu-btn:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: scale(1.05);
    }

    /* ============================================
       SECCIÓN HERO (Portada principal)
       ============================================ */
    .hero {
      margin-top: 160px;
      padding: 80px 24px 60px;
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, var(--light) 0%, #e8f5e9 50%, var(--bg-color) 100%);
    }

    .hero-content {
      max-width: 1400px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

    .hero-text h1 {
      font-family: 'Orbitron', monospace;
      font-size: 3.8rem;
      font-weight: 900;
      line-height: 1.1;
      margin-bottom: 20px;
      background: var(--gradient-1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      position: relative;
    }

    .hero-text h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 100px;
      height: 4px;
      background: var(--gradient-1);
      border-radius: 2px;
    }

    .hero-subtitle {
      font-size: 1.2rem;
      color: var(--text-color);
      margin-bottom: 30px;
      line-height: 1.6;
      font-weight: 400;
    }

    .counter-section {
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .counter-item {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 12px 16px;
      text-align: center;
      min-width: 100px;
      box-shadow: var(--shadow-sm);
    }

    .counter-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary);
      line-height: 1;
    }

    .counter-label {
      font-size: 0.75rem;
      color: var(--text-secondary);
      margin-top: 2px;
    }

    .hero-buttons {
      display: flex;
      gap: 18px;
      flex-wrap: wrap;
    }

    .btn-neon {
      padding: 14px 40px;
      border: none;
      border-radius: 50px;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: all 0.3s;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      flex-shrink: 0;
      box-shadow: var(--shadow-md);
    }

    .btn-primary {
      background: var(--gradient-1);
      color: var(--light-text);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.9);
      color: var(--primary);
      border: 2px solid var(--primary);
    }

    .btn-secondary:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }

    .hero-visual {
      position: relative;
      height: 480px;
      perspective: 1200px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .rotating-cards {
      position: absolute;
      width: 100%;
      height: 100%;
      transform-style: preserve-3d;
      animation: rotate3d 25s infinite linear;
    }

    @keyframes rotate3d {
      0% { transform: rotateY(0deg); }
      100% { transform: rotateY(360deg); }
    }

    .float-card {
      position: absolute;
      width: 240px;
      height: 340px;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      background: var(--glass-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 20px;
      box-shadow: var(--shadow-lg);
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .float-card:hover {
      transform: translate(-50%, -50%) scale(1.05);
    }

    .float-card:nth-child(1) {
      transform: translate(-50%, -50%) translateZ(200px);
    }

    .float-card:nth-child(2) {
      transform: translate(-50%, -50%) rotateY(120deg) translateZ(200px);
    }

    .float-card:nth-child(3) {
      transform: translate(-50%, -50%) rotateY(240deg) translateZ(200px);
    }

    .float-card-image {
      width: 100%;
      height: 140px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
      border-radius: 12px;
      overflow: hidden;
      background: rgba(255, 255, 255, 0.2);
    }

    .float-card-image img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform 0.5s;
    }

    .float-card:hover .float-card-image img {
      transform: scale(1.1);
    }

    .float-card h3 {
      margin-bottom: 10px;
      font-size: 18px;
      color: var(--primary);
      font-weight: 600;
    }

    .float-card p {
      color: var(--text-secondary);
      font-size: 14px;
      margin: 0;
      line-height: 1.5;
    }

    /* ============================================
       SECCIÓN DE CATEGORÍAS
       ============================================ */
    .categories {
      padding: 80px 24px;
      max-width: 1400px;
      margin: 0 auto;
      background: var(--bg-color);
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-title {
      font-family: 'Orbitron', monospace;
      font-size: 2.8rem;
      font-weight: 700;
      background: var(--gradient-1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 12px;
    }

    .section-subtitle {
      font-size: 1.2rem;
      color: var(--text-secondary);
      margin-bottom: 20px;
      font-weight: 400;
    }

    .section-line {
      width: 100px;
      height: 3px;
      background: var(--gradient-1);
      margin: 20px auto;
      border-radius: 2px;
      position: relative;
    }

    .section-line::after {
      content: '';
      position: absolute;
      top: -4px;
      left: 50%;
      transform: translateX(-50%);
      width: 8px;
      height: 8px;
      background: var(--primary);
      border-radius: 50%;
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    }

    .hex-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 30px;
    }

    .hex-item {
      position: relative;
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      perspective: 1000px;
    }

    .hex-item:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 40px rgba(16, 185, 129, 0.2);
    }

    .hex-content {
      background: var(--glass-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      border-radius: 16px;
      padding: 35px 20px;
      text-align: center;
      position: relative;
      overflow: hidden;
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-md);
      transition: all 0.3s ease;
    }

    .hex-content::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .hex-item:hover .hex-content::before {
      opacity: 0.1;
    }

    .hex-content::after {
      content: '';
      position: absolute;
      inset: 1px;
      background: var(--card-bg);
      border-radius: 15px;
    }

    .hex-icon {
      font-size: 48px;
      margin-bottom: 15px;
      background: var(--gradient-1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      z-index: 1;
      transition: transform 0.3s ease;
    }

    .hex-item:hover .hex-icon {
      transform: scale(1.1);
    }

    .hex-title {
      font-weight: 600;
      font-size: 16px;
      z-index: 1;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text-color);
      text-align: center;
    }

    .hex-description {
      font-size: 13px;
      z-index: 1;
      color: var(--text-secondary);
      margin-top: 8px;
      line-height: 1.4;
    }

    /* ============================================
       SECCIÓN DE FILTROS DE PRODUCTOS
       ============================================ */
    .filter-section {
      padding: 40px 24px;
      max-width: 1400px;
      margin: 0 auto;
      background: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .filter-container {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 30px;
      display: flex;
      gap: 18px;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: var(--shadow-sm);
    }

    .filter-group {
      flex: 1;
      min-width: 180px;
    }

    .filter-label {
      display: block;
      margin-bottom: 8px;
      color: var(--text-color);
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .filter-input,
    .filter-select {
      width: 100%;
      padding: 12px 16px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid var(--glass-border);
      border-radius: 14px;
      color: var(--text-color);
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .filter-input:focus,
    .filter-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
      background: white;
    }

    .filter-select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 16px center;
      padding-right: 40px;
      cursor: pointer;
    }

    .filter-actions {
      display: flex;
      gap: 15px;
      min-width: 200px;
      flex-wrap: wrap;
    }

    .filter-btn {
      flex: 1;
      min-width: 90px;
    }

    /* ============================================
       SECCIÓN DE PRODUCTOS
       ============================================ */
    .products {
      padding: 60px 24px;
      max-width: 1400px;
      margin: 0 auto;
      background: var(--bg-color);
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
    }

    .product-card {
      background: var(--card-bg);
      border: 1px solid var(--glass-border);
      border-radius: 22px;
      overflow: hidden;
      position: relative;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      cursor: pointer;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .product-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: var(--gradient-1);
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }

    .product-card:hover::before {
      transform: scaleX(1);
    }

    .product-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary);
    }

    .product-image-container {
      position: relative;
      height: 240px;
      overflow: hidden;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .product-image-container img {
      width: auto;
      height: auto;
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform 0.5s ease;
    }

    .product-card:hover .product-image-container img {
      transform: scale(1.12);
    }

    .product-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      padding: 8px 15px;
      background: var(--gradient-2);
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--light-text);
      box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }

    .product-details {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .product-title {
      font-size: 19px;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-color);
      line-height: 1.4;
    }

    .product-desc {
      color: var(--text-secondary);
      font-size: 14px;
      line-height: 1.5;
      margin-bottom: 18px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      flex: 1;
    }

    .product-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }

    .product-price {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary);
    }

    .product-actions {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }

    .qty-control {
      display: flex;
      align-items: center;
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--glass-border);
    }

    .qty-btn {
      width: 32px;
      height: 32px;
      border: none;
      background: transparent;
      color: var(--primary);
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .qty-btn:hover {
      background: rgba(16, 185, 129, 0.1);
      color: var(--secondary);
    }

    .qty-input {
      width: 45px;
      text-align: center;
      border: none;
      background: transparent;
      color: var(--text-color);
      font-weight: 600;
      font-size: 14px;
      padding: 0 5px;
    }

    .add-cart-btn {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: var(--gradient-1);
      border: none;
      color: var(--light-text);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      font-size: 16px;
      box-shadow: var(--shadow-md);
    }

    .add-cart-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 0 25px rgba(16, 185, 129, 0.6);
    }

    .stock-info {
      margin-top: 12px;
      font-size: 12px;
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* ============================================
       PAGINACIÓN
       ============================================ */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 60px;
      flex-wrap: wrap;
    }

    .page-item {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 14px;
      color: var(--text-color);
      text-decoration: none;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }

    .page-item::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
      transition: all 0.3s ease;
      transform: translate(-50%, -50%);
    }

    .page-item:hover::before,
    .page-item.active::before {
      width: 100%;
      height: 100%;
    }

    .page-item:hover,
    .page-item.active {
      border-color: var(--primary);
      color: var(--primary);
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }

    /* ============================================
       CARRITO FLOTANTE
       ============================================ */
    .floating-cart {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 70px;
      height: 70px;
      background: var(--gradient-1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: var(--shadow-lg);
      transition: all 0.3s ease;
      z-index: 999;
      animation: float-cart 4s ease-in-out infinite;
      font-size: 24px;
      border: 3px solid white;
    }

    @keyframes float-cart {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .floating-cart:hover {
      transform: scale(1.1);
      box-shadow: 0 15px 40px rgba(16, 185, 129, 0.7);
    }

    .cart-count {
      position: absolute;
      top: -6px;
      right: -6px;
      width: 26px;
      height: 26px;
      background: var(--gradient-2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      color: var(--light-text);
      border: 2px solid white;
    }

    /* ============================================
       ESTILOS PARA PRODUCTOS AGOTADOS
       ============================================ */
    .product-card.agotado {
      opacity: 0.6;
      filter: grayscale(0.4);
    }

    .product-card.agotado:hover {
      transform: none;
      cursor: not-allowed;
    }

    .stock-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      padding: 8px 15px;
      border-radius: 16px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: white;
      z-index: 10;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .stock-disponible {
      background: var(--gradient-2);
    }

    .stock-bajo {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .stock-agotado {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .btn-agotado {
      background: #9ca3af !important;
      color: white !important;
      cursor: not-allowed !important;
    }

    .btn-agotado:hover {
      transform: none !important;
      box-shadow: none !important;
    }

    /* ============================================
       SECCIÓN PRODUCTOS AGOTADOS
       ============================================ */
    .agotados-section {
      margin-top: 80px;
      padding: 40px 24px;
      background: rgba(254, 252, 232, 0.5);
      border-radius: 24px;
      border: 1px solid rgba(251, 191, 36, 0.2);
    }

    .agotados-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .agotados-title {
      font-family: 'Orbitron', monospace;
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 12px;
      background: linear-gradient(90deg, #f59e0b, #d97706);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .agotados-subtitle {
      font-size: 1.1rem;
      color: var(--text-secondary);
    }

    /* ============================================
       ESTILOS PARA DESCUENTOS
       ============================================ */
    .descuento-badge {
      position: absolute;
      top: 15px;
      left: 15px;
      padding: 6px 12px;
      background: linear-gradient(135deg, #e30044, #c1003a);
      border-radius: 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: white;
      z-index: 10;
      box-shadow: 0 4px 12px rgba(227, 0, 68, 0.3);
    }

    .precio-regular {
      text-decoration: line-through;
      color: #9ca3af;
      font-size: 15px;
      margin-right: 8px;
    }

    .precio-oferta {
      color: var(--red-accent);
      font-weight: 700;
    }

    .precio-container {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .precio-linea {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .etiqueta-oferta {
      background: var(--red-accent);
      color: white;
      padding: 3px 10px;
      border-radius: 14px;
      font-size: 11px;
      font-weight: bold;
    }

    /* ============================================
       RESPONSIVE DESIGN MEJORADO
       ============================================ */

    /* Pantallas extra grandes (4K, TV) - 1920px+ */
    @media (min-width: 1920px) {
      .commercial-header-container {
        max-width: 1800px;
      }
      
      .hero-content,
      .categories,
      .filter-section,
      .products {
        max-width: 1800px;
      }
      
      .products-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 35px;
      }
      
      .hero-text h1 {
        font-size: 4.5rem;
      }
    }

    /* Pantallas grandes (Desktop) - 1200px a 1919px */
    @media (min-width: 1200px) and (max-width: 1919px) {
      .commercial-header-container {
        max-width: 1400px;
      }
      
      .hero-content {
        grid-template-columns: 1fr 1fr;
      }
      
      .products-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
      }
      
      .hex-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    /* Laptops y Tablets grandes - 992px a 1199px */
    @media (max-width: 1199px) and (min-width: 992px) {
      .commercial-header-container {
        max-width: 1200px;
        padding: 0 20px;
      }
      
      .hero-content {
        grid-template-columns: 1fr 1fr;
        gap: 45px;
      }
      
      .hero-text h1 {
        font-size: 3.2rem;
      }
      
      .products-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
      }
      
      .hex-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
      }
      
      .commercial-nav {
        gap: 18px;
      }
      
      .commercial-search {
        margin: 0 25px;
        max-width: 400px;
      }
    }

    /* Tablets - 768px a 991px */
    @media (max-width: 991px) and (min-width: 768px) {
      .commercial-header-container {
        padding: 0 20px;
      }
      
      .header-top {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
      }
      
      .commercial-logo {
        text-align: center;
        font-size: 24px;
      }
      
      .commercial-search {
        margin: 0;
        max-width: 100%;
      }
      
      .commercial-nav {
        justify-content: center;
        gap: 25px;
        display: flex;
      }
      
      .hero {
        margin-top: 190px;
        padding: 60px 20px 40px;
      }
      
      .hero-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 40px;
      }
      
      .hero-text h1 {
        font-size: 3rem;
      }
      
      .hero-visual {
        height: 400px;
      }
      
      .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
      }
      
      .hex-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
      }
      
      .filter-container {
        flex-direction: column;
        gap: 15px;
      }
      
      .filter-group {
        width: 100%;
      }
      
      .mobile-menu-btn {
        display: none;
      }
    }

    /* Tablets pequeñas y móviles grandes - 576px a 767px */
    @media (max-width: 767px) and (min-width: 576px) {
      .commercial-header-container {
        padding: 0 16px;
      }
      
      .commercial-logo {
        font-size: 22px;
      }
      
      .commercial-nav {
        display: none;
      }
      
      .mobile-menu-btn {
        display: flex;
      }
      
      .hero {
        margin-top: 160px;
        padding: 50px 16px 35px;
      }
      
      .hero-text h1 {
        font-size: 2.4rem;
      }
      
      .hero-subtitle {
        font-size: 1.1rem;
      }
      
      .hero-buttons {
        justify-content: center;
      }
      
      .btn-neon {
        padding: 12px 30px;
        font-size: 14px;
      }
      
      .hero-visual {
        height: 340px;
      }
      
      .float-card {
        width: 200px;
        height: 280px;
        padding: 18px;
      }
      
      .products-grid {
        grid-template-columns: 1fr;
        gap: 22px;
      }
      
      .hex-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }
      
      .section-title {
        font-size: 2.2rem;
      }
      
      .product-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .product-actions {
        width: 100%;
        justify-content: space-between;
      }
      
      .floating-cart {
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        font-size: 20px;
      }
      
      .cart-count {
        width: 24px;
        height: 24px;
        font-size: 11px;
      }
    }

    /* Móviles medianos - 425px a 575px */
    @media (max-width: 575px) and (min-width: 425px) {
      .commercial-header-container {
        padding: 0 14px;
      }
      
      .commercial-logo {
        font-size: 20px;
        justify-content: center;
        width: 100%;
      }
      
      .commercial-nav {
        display: none;
      }
      
      .mobile-menu-btn {
        display: flex;
        position: absolute;
        right: 14px;
        top: 14px;
      }
      
      .header-top {
        flex-direction: column;
        gap: 12px;
        position: relative;
      }
      
      .commercial-search {
        margin: 0;
        max-width: 100%;
      }
      
      .hero {
        margin-top: 150px;
        padding: 40px 14px 30px;
      }
      
      .hero-text h1 {
        font-size: 2rem;
      }
      
      .hero-subtitle {
        font-size: 1rem;
        margin-bottom: 25px;
      }
      
      .hero-buttons {
        flex-direction: column;
        align-items: center;
        gap: 15px;
      }
      
      .btn-neon {
        width: 100%;
        max-width: 240px;
        padding: 12px 20px;
      }
      
      .hero-visual {
        height: 300px;
      }
      
      .float-card {
        width: 160px;
        height: 240px;
        padding: 15px;
      }
      
      .float-card h3 {
        font-size: 16px;
      }
      
      .float-card p {
        font-size: 12px;
      }
      
      .products-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      
      .hex-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      
      .section-title {
        font-size: 2rem;
      }
      
      .categories,
      .filter-section,
      .products {
        padding: 40px 16px;
      }
      
      .filter-container {
        padding: 24px;
        gap: 15px;
      }
      
      .product-details {
        padding: 20px;
      }
      
      .product-title {
        font-size: 18px;
      }
      
      .product-price {
        font-size: 20px;
      }
      
      .pagination {
        gap: 10px;
      }
      
      .page-item {
        width: 42px;
        height: 42px;
        font-size: 14px;
      }
      
      .floating-cart {
        width: 55px;
        height: 55px;
        bottom: 15px;
        right: 15px;
        font-size: 18px;
      }
      
      .cart-count {
        width: 22px;
        height: 22px;
        font-size: 9px;
      }
    }

    /* Móviles pequeños - hasta 424px */
    @media (max-width: 424px) {
      .commercial-header-container {
        padding: 0 12px;
      }
      
      .commercial-logo {
        font-size: 18px;
      }
      
      .hero-text h1 {
        font-size: 1.8rem;
      }
      
      .section-title {
        font-size: 1.8rem;
      }
      
      .agotados-title {
        font-size: 1.8rem;
      }
      
      .hex-content {
        padding: 30px 15px;
        height: 160px;
      }
      
      .hex-icon {
        font-size: 36px;
      }
      
      .hex-title {
        font-size: 14px;
      }
      
      .hex-description {
        font-size: 11px;
      }
      
      .product-image-container {
        height: 200px;
      }
      
      .filter-container {
        padding: 20px;
        gap: 12px;
      }
      
      .filter-input,
      .filter-select {
        padding: 10px 14px;
        font-size: 13px;
      }
      
      .btn-neon {
        font-size: 13px;
        padding: 10px 18px;
      }
      
      .floating-cart {
        width: 50px;
        height: 50px;
        bottom: 12px;
        right: 12px;
        font-size: 16px;
      }
      
      .cart-count {
        width: 20px;
        height: 20px;
        font-size: 9px;
      }
    }

    /* Móviles muy pequeños - hasta 320px */
    @media (max-width: 320px) {
      .commercial-header-container {
        padding: 0 10px;
      }
      
      .commercial-logo {
        font-size: 16px;
      }
      
      .hero-text h1 {
        font-size: 1.6rem;
      }
      
      .hero-subtitle {
        font-size: 0.9rem;
      }
      
      .section-title {
        font-size: 1.6rem;
      }
      
      .products-grid {
        gap: 16px;
      }
      
      .product-details {
        padding: 16px;
      }
      
      .product-title {
        font-size: 16px;
      }
      
      .product-desc {
        font-size: 13px;
      }
      
      .product-price {
        font-size: 18px;
      }
    }
  </style>   
</head>
<body>
  <!-- Particles Background -->
  <div class="particles" id="particles"></div>

  <!-- ENCABEZADO RESPONSIVE -->
  <header class="commercial-header">
    <div class="commercial-header-container">
      <div class="header-top">
        <div class="commercial-logo">
          <i class="fas fa-heartbeat"></i> Salud<span>Perfecta</span>
        </div>
        
        <div class="commercial-search">
          <div class="commercial-search-bar" id="searchContainer">
            <input type="text" id="searchInput" class="commercial-search-input" 
                   placeholder="Buscar productos..." 
                   value="<?= htmlspecialchars($busqueda) ?>">
            <button type="button" id="searchButton" class="commercial-search-btn">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>
        
        <nav class="commercial-nav">
          <a href="index.php" class="commercial-nav-item">
            <i class="fas fa-home"></i> Inicio
          </a>
          <a href="#productos" class="commercial-nav-item">
            <i class="fas fa-cube"></i> Productos
          </a>
          <a href="#categorias" class="commercial-nav-item">
            <i class="fas fa-layer-group"></i> Categorías
          </a>
          <a href="carrito.php" class="commercial-nav-item">
            <i class="fas fa-shopping-bag"></i> Carrito
          </a>
        </nav>
        
        <div class="mobile-menu-btn">
          <i class="fas fa-bars"></i>
        </div>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <div class="hero-text">
        <h1>Evoluciona tu Bienestar</h1>
        <p class="hero-subtitle">
          Descubre la nueva dimensión de la salud con suplementos de alta tecnología 
          diseñados para potenciar tu rendimiento físico y mental.
          <br><br>
          <strong style="color: var(--primary);"><?= $totalProductos ?> productos disponibles</strong>
        </p>
        <div class="counter-section">
          <div class="counter-item">
            <div class="counter-value"><?= $totalProductos ?></div>
            <div class="counter-label">Productos</div>
          </div>
          <div class="counter-item">
            <div class="counter-value">100+</div>
            <div class="counter-label">Clientes</div>
          </div>
          <div class="counter-item">
            <div class="counter-value">24/7</div>
            <div class="counter-label">Soporte</div>
          </div>
        </div>
        <div class="hero-buttons">
          <button class="btn-neon btn-primary" onclick="scrollToSection('productos')">
            <i class="fas fa-shopping-cart"></i> Descubre lo Último
          </button>
          <button class="btn-neon btn-secondary" onclick="scrollToSection('productos')">
            <i class="fas fa-cubes"></i> Ver Catálogo
          </button>
        </div>
      </div>
      
      <div class="hero-visual">
        <div class="rotating-cards">
          <div class="float-card">
            <div class="float-card-image">
              <img src="https://encrypted-tbn3.gstatic.com/shopping?q=tbn:ANd9GcQFCLzrfqM47szasSmHld27Dn8D60tqVTgfZNTStma0xshIu8gwcJVmXkYOkmbSrtL4SQAitsLjbM4FDizIGL_GVdUUg4lglPr9QMeL1s7Lpim26y4OlF4bbnOW5-VjXA&usqp=CAc" alt="Immunocal">
            </div>
            <h3 style="color: var(--neon-cyan); margin-bottom: 15px;">Immunocal</h3>
            <p style="color: var(--text-secondary); font-size: 14px;">
              Potenciador inmunológico de última generación
            </p>
          </div>
          <div class="float-card">
            <div class="float-card-image">
              <img src="https://m.media-amazon.com/images/I/31FtnTFB1ZL._UF1000,1000_QL80_.jpg" alt="Omega Gen V">
            </div>
            <h3 style="color: var(--neon-purple); margin-bottom: 15px;">Omega Gen V</h3>
            <p style="color: var(--text-secondary); font-size: 14px;">
              Energía cuántica para tu día a día
            </p>
          </div>
          <div class="float-card">
            <div class="float-card-image">
              <img src="https://melyfarma.pe/wp-content/uploads/2025/03/paracetamol2-1-scaled.jpg" alt="Paracetamol">
            </div>
            <h3 style="color: var(--neon-pink); margin-bottom: 15px;">Paracetamol</h3>
            <p style="color: var(--text-secondary); font-size: 14px;">
              Rendimiento cognitivo optimizado
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Categories Section -->
  <section class="categories" id="categorias">
    <div class="section-header">
      <h2 class="section-title">Categorías del Futuro</h2>
      <p class="section-subtitle">Encuentra productos específicos para tus necesidades</p>
      <div class="section-line"></div>
    </div>
    
    <div class="hex-grid">
      <div class="hex-item">
        <div class="hex-content">
          <i class="fas fa-shield-virus hex-icon"></i>
          <h3 class="hex-title">Soporte Inmunológico</h3>
          <p class="hex-description">Fortalece tu sistema inmune con nuestras fórmulas avanzadas</p>
        </div>
      </div>
      
      <div class="hex-item">
        <div class="hex-content">
          <i class="fas fa-heartbeat hex-icon"></i>
          <h3 class="hex-title">Bienestar Diario</h3>
          <p class="hex-description">Para tu rutina diaria de salud y vitalidad</p>
        </div>
      </div>
      
      <div class="hex-item">
        <div class="hex-content">
          <i class="fas fa-bolt hex-icon"></i>
          <h3 class="hex-title">Rendimiento Deportivo</h3>
          <p class="hex-description">Maximiza tu rendimiento físico con nutrientes premium</p>
        </div>
      </div>
      
      <div class="hex-item">
        <div class="hex-content">
          <i class="fas fa-brain hex-icon"></i>
          <h3 class="hex-title">Salud Cerebral</h3>
          <p class="hex-description">Potencia tu mente y memoria con ingredientes naturales</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Filter Section -->
  <section class="filter-section">
    <div class="filter-container" id="filterContainer">
      <div class="filter-group">
        <label class="filter-label">Buscar Producto</label>
        <input type="text" id="buscarInput" class="filter-input" 
               placeholder="Nombre del producto..." 
               value="<?= htmlspecialchars($busqueda) ?>">
      </div>
      
      <div class="filter-group">
        <label class="filter-label">Categoría</label>
        <select id="categoriaSelect" class="filter-select">
          <option value="">Todas las categorías</option>
          <option value="SoporteInmunologico" <?= $categoria=="SoporteInmunologico"?"selected":"" ?>>Soporte Inmunológico</option>
          <option value="BienestarDiario" <?= $categoria=="BienestarDiario"?"selected":"" ?>>Bienestar Diario</option>
          <option value="EnvejecimientoSaludable" <?= $categoria=="EnvejecimientoSaludable"?"selected":"" ?>>Envejecimiento Saludable</option>
          <option value="RendimientoDeportivo" <?= $categoria=="RendimientoDeportivo"?"selected":"" ?>>Rendimiento Deportivo</option>
          <option value="EstresEstadoAnimo" <?= $categoria=="EstresEstadoAnimo"?"selected":"" ?>>Estrés y Estado de Ánimo</option>
          <option value="SaludCerebral" <?= $categoria=="SaludCerebral"?"selected":"" ?>>Salud Cerebral</option>
          <option value="Promociones" <?= $categoria=="Promociones"?"selected":"" ?>>Promociones</option>
          <option value="PaquetesSalud" <?= $categoria=="PaquetesSalud"?"selected":"" ?>>Paquetes de Salud</option>
          <option value="Accesorios" <?= $categoria=="Accesorios"?"selected":"" ?>>Accesorios</option>
          <option value="Vitaminas" <?= $categoria=="Vitaminas"?"selected":"" ?>>Vitaminas</option>
          <option value="Minerales" <?= $categoria=="Minerales"?"selected":"" ?>>Minerales</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label class="filter-label">Ordenar Por</label>
        <select id="ordenSelect" class="filter-select">
          <option value="">Por defecto</option>
          <option value="popular" <?= $orden=="popular"?"selected":"" ?>>Más Popular</option>
          <option value="precio_menor" <?= $orden=="precio_menor"?"selected":"" ?>>Precio ↑</option>
          <option value="precio_mayor" <?= $orden=="precio_mayor"?"selected":"" ?>>Precio ↓</option>
          <option value="nuevo" <?= $orden=="nuevo"?"selected":"" ?>>Más Reciente</option>
        </select>
      </div>
      
      <div class="filter-actions">
        <button type="button" id="aplicarFiltros" class="btn-neon btn-primary filter-btn">
          <i class="fas fa-filter"></i> Aplicar
        </button>
        <button type="button" id="limpiarFiltros" class="btn-neon btn-secondary filter-btn">
          <i class="fas fa-eraser"></i> Limpiar
        </button>
      </div>
    </div>
  </section>

  <!-- Products Section -->
  <section class="products" id="productos">
    <div class="section-header">
      <h2 class="section-title">
        <?php if ($busqueda != ""): ?>
          Resultados para "<?= htmlspecialchars($busqueda) ?>"
        <?php else: ?>
          Productos Disponibles
        <?php endif; ?>
      </h2>
      <div class="section-line"></div>
      <p class="text-gray-600">
        <?= $totalProductos ?> productos encontrados - 
        <?php if ($busqueda != "" || $categoria != "" || $orden != ""): ?> 
          <a href="#" onclick="limpiarFiltros(); return false;" style="color: var(--primary); text-decoration: underline;">
            Ver todos los productos
          </a>
        <?php endif; ?>
      </p>
    </div>
    
    <div class="products-grid">
      <?php if ($resultado && $resultado->num_rows > 0): ?>
        <?php while ($producto = $resultado->fetch_assoc()): ?>
          <div class="product-card">
            <!-- Stock Badge -->
            <div class="stock-badge <?= $producto['stock'] <= 5 ? 'stock-bajo' : 'stock-disponible' ?>">
              <?php if ($producto['stock'] <= 5): ?>
                ¡Últimas <?= $producto['stock'] ?>!
              <?php else: ?>
                En Stock (<?= $producto['stock'] ?>)
              <?php endif; ?>
            </div>

            <!-- Envío Gratis Badge -->
            <?php if ($producto['envio_gratis']): ?>
              <div class="product-badge" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); top: 15px; left: auto; right: 15px;">
                <i class="fas fa-truck mr-1"></i>Envío Gratis
              </div>
            <?php endif; ?>

            <!-- Descuento Badge -->
            <?php if ($producto['descuento_porcentaje'] > 0 || $producto['descuento_monto'] > 0): ?>
              <div class="descuento-badge">
                <?php if ($producto['descuento_porcentaje'] > 0): ?>
                  -<?= number_format($producto['descuento_porcentaje'], 0) ?>%
                <?php else: ?>
                  OFERTA
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <!-- Product Image -->
            <div class="product-image-container">
              <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                <img src="<?= htmlspecialchars($producto['imagen']) ?>" 
                     alt="<?= htmlspecialchars($producto['nombre']) ?>">
              <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: #9ca3af;">
                  <i class="fas fa-image fa-3x"></i>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="product-details">
              <h3 class="product-title"><?= htmlspecialchars($producto['nombre']) ?></h3>
              <p class="product-desc"><?= htmlspecialchars($producto['descripcion']) ?></p>
              
              <div class="product-footer">
                <div class="product-price">
                  <?php if ($producto['precio_regular'] > $producto['precio_venta']): ?>
                    <div class="precio-container">
                      <div class="precio-linea">
                        <span class="precio-regular">S/ <?= number_format($producto['precio_regular'], 2) ?></span>
                        <span class="etiqueta-oferta">-<?= number_format($producto['descuento_porcentaje'], 0) ?>%</span>
                      </div>
                      <span class="precio-oferta">S/ <?= number_format($producto['precio_venta'], 2) ?></span>
                    </div>
                  <?php else: ?>
                    <span>S/ <?= number_format($producto['precio_venta'], 2) ?></span>
                  <?php endif; ?>
                </div>
                
                <div class="product-actions">
                  <div class="qty-control">
                    <button class="qty-btn minus-btn">-</button>
                    <input type="number" class="qty-input" value="1" min="1" max="<?= $producto['stock'] ?>" data-id="<?= $producto['id_producto'] ?>">
                    <button class="qty-btn plus-btn">+</button>
                  </div>
                  <button class="add-cart-btn" data-id="<?= $producto['id_producto'] ?>">
                    <i class="fas fa-shopping-cart"></i>
                  </button>
                </div>
              </div>

              <!-- Stock Info -->
              <div class="stock-info">
                <i class="fas fa-box"></i> 
                <?= $producto['stock'] ?> unidades disponibles
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px;">
          <i class="fas fa-box-open" style="font-size: 5rem; color: #d1d5db; margin-bottom: 25px;"></i>
          <h3 style="font-size: 1.8rem; color: #6b7280; margin-bottom: 15px;">
            <?php if ($busqueda != ""): ?>
              No se encontraron productos para "<?= htmlspecialchars($busqueda) ?>"
            <?php else: ?>
              No hay productos disponibles
            <?php endif; ?>
          </h3>
          <p style="color: #9ca3af; margin-bottom: 25px; font-size: 1.1rem;">
            <?php if ($busqueda != "" || $categoria != ""): ?>
              Intenta con otros términos de búsqueda o <a href="#" onclick="limpiarFiltros(); return false;" style="color: var(--primary);">ver todos los productos</a>
            <?php else: ?>
              Todos nuestros productos están actualmente agotados.
            <?php endif; ?>
          </p>
          <button class="btn-neon btn-primary" onclick="window.location.href='index.php'">
            <i class="fas fa-home"></i> Volver al Inicio
          </button>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPaginas > 1): ?>
      <div class="pagination">
        <?php if($pagina > 1): ?>
          <a href="?pagina=<?= $pagina-1 ?>&buscar=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($categoria) ?>&orden=<?= urlencode($orden) ?>" 
             class="page-item">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <a href="?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($categoria) ?>&orden=<?= urlencode($orden) ?>" 
             class="page-item <?= $i == $pagina ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
        
        <?php if($pagina < $totalPaginas): ?>
          <a href="?pagina=<?= $pagina+1 ?>&buscar=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($categoria) ?>&orden=<?= urlencode($orden) ?>" 
             class="page-item">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Productos Agotados Section -->
  <?php if ($totalAgotados > 0): ?>
  <section class="agotados-section">
    <div class="agotados-header">
      <h2 class="agotados-title">Productos Agotados</h2>
      <p class="agotados-subtitle">Estos productos están temporalmente fuera de stock</p>
      <div class="section-line"></div>
    </div>
    
    <div class="products-grid">
      <?php while ($productoAgotado = $resultadoAgotados->fetch_assoc()): ?>
        <div class="product-card agotado">
          <!-- Agotado Badge -->
          <div class="stock-badge stock-agotado">
            Agotado
          </div>

          <!-- Envío Gratis Badge -->
          <?php if ($productoAgotado['envio_gratis']): ?>
            <div class="product-badge" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); top: 15px; left: auto; right: 15px;">
              <i class="fas fa-truck mr-1"></i>Envío Gratis
            </div>
          <?php endif; ?>

          <!-- Product Image -->
          <div class="product-image-container">
            <?php if (!empty($productoAgotado['imagen']) && file_exists($productoAgotado['imagen'])): ?>
              <img src="<?= htmlspecialchars($productoAgotado['imagen']) ?>" 
                   alt="<?= htmlspecialchars($productoAgotado['nombre']) ?>"
                   style="filter: grayscale(0.5); opacity: 0.7;">
            <?php else: ?>
              <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: #9ca3af;">
                <i class="fas fa-image fa-3x"></i>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="product-details">
            <h3 class="product-title" style="color: #9ca3af;"><?= htmlspecialchars($productoAgotado['nombre']) ?></h3>
            <p class="product-desc" style="color: #9ca3af;"><?= htmlspecialchars($productoAgotado['descripcion']) ?></p>
            
            <div class="product-footer">
              <div class="product-price" style="color: #9ca3af;">
                <?php if ($productoAgotado['precio_regular'] > $productoAgotado['precio_venta']): ?>
                  <div class="precio-container">
                    <div class="precio-linea">
                      <span class="precio-regular">S/ <?= number_format($productoAgotado['precio_regular'], 2) ?></span>
                    </div>
                    <span>S/ <?= number_format($productoAgotado['precio_venta'], 2) ?></span>
                  </div>
                <?php else: ?>
                  <span>S/ <?= number_format($productoAgotado['precio_venta'], 2) ?></span>
                <?php endif; ?>
              </div>
              
              <div class="product-actions">
                <div class="qty-control" style="opacity: 0.5;">
                  <button class="qty-btn minus-btn" disabled>-</button>
                  <input type="number" class="qty-input" value="0" min="0" max="0" disabled style="color: #9ca3af;">
                  <button class="qty-btn plus-btn" disabled>+</button>
                </div>
                <button class="add-cart-btn btn-agotado" disabled>
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>

            <!-- Stock Info -->
            <div class="stock-info" style="color: #ef4444;">
              <i class="fas fa-exclamation-triangle"></i> 
              Producto agotado
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Floating Cart Button -->
  <a href="carrito.php" class="floating-cart">
    <i class="fas fa-shopping-cart"></i>
    <?php
    $count = 0;
    if (isset($_SESSION['carrito'])) {
      foreach ($_SESSION['carrito'] as $c) {
        $count += $c['cantidad'];
      }
    }
    ?>
    <span class="cart-count"><?= $count ?></span>
  </a>

  <script>
    // Generate particles
    const particlesContainer = document.getElementById('particles');
    for(let i = 0; i < 60; i++) {
      const particle = document.createElement('div');
      particle.className = 'particle';
      particle.style.left = Math.random() * 100 + '%';
      particle.style.animationDelay = Math.random() * 20 + 's';
      particle.style.animationDuration = (15 + Math.random() * 10) + 's';
      particlesContainer.appendChild(particle);
    }

    // ============================================
    // CORRECCIÓN DEL PROBLEMA DEL CAMPO DE BÚSQUEDA
    // ============================================
    
    // Prevenir que el campo de búsqueda se active automáticamente
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchContainer = document.getElementById('searchContainer');
        
        // Solo activar el campo de búsqueda cuando se haga clic directamente en él
        // Removido el código que quitaba el foco al hacer clic en otro lugar para evitar ocultar el menú
    });

    // ============================================
    // SISTEMA DE FILTROS MEJORADO
    // ============================================

    // Función para aplicar filtros
    function aplicarFiltros() {
        const buscar = document.getElementById('buscarInput').value.trim();
        const categoria = document.getElementById('categoriaSelect').value;
        const orden = document.getElementById('ordenSelect').value;
        const envio = document.getElementById('envioSelect').value;
        
        const params = new URLSearchParams();
        
        if (buscar) params.set('buscar', buscar);
        if (categoria) params.set('categoria', categoria);
        if (orden) params.set('orden', orden);
        if (envio) params.set('envio_gratis', envio);
        
        // Redirigir con los nuevos parámetros
        const newUrl = 'index.php?' + params.toString();
        window.location.href = newUrl;
    }

    // Función para limpiar filtros
    function limpiarFiltros() {
        // Redirigir a la página sin parámetros
        window.location.href = 'index.php';
    }

    // Función para buscar desde el header
    function buscarDesdeHeader() {
        const searchInput = document.getElementById('searchInput');
        const buscar = searchInput.value.trim();
        
        if (buscar) {
            const params = new URLSearchParams();
            params.set('buscar', buscar);
            window.location.href = 'index.php?' + params.toString();
        } else {
            // Si está vacío, limpiar búsqueda
            limpiarFiltros();
        }
    }

    // Función para scroll suave
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Inicializar eventos cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Botón aplicar filtros
        const aplicarFiltrosBtn = document.getElementById('aplicarFiltros');
        if (aplicarFiltrosBtn) {
            aplicarFiltrosBtn.addEventListener('click', aplicarFiltros);
        }
        
        // Botón limpiar filtros
        const limpiarFiltrosBtn = document.getElementById('limpiarFiltros');
        if (limpiarFiltrosBtn) {
            limpiarFiltrosBtn.addEventListener('click', limpiarFiltros);
        }
        
        // Búsqueda desde el header
        const searchButton = document.getElementById('searchButton');
        const searchInput = document.getElementById('searchInput');
        
        if (searchButton) {
            searchButton.addEventListener('click', buscarDesdeHeader);
        }
        
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    buscarDesdeHeader();
                }
            });
        }
        
        // Prevenir que los selects envíen automáticamente
        const categoriaSelect = document.getElementById('categoriaSelect');
        const ordenSelect = document.getElementById('ordenSelect');
        const envioSelect = document.getElementById('envioSelect');
        
        if (categoriaSelect) {
            categoriaSelect.addEventListener('change', function(e) {
                e.preventDefault();
                // No hacer nada - solo el botón aplica los filtros
            });
        }
        
        if (ordenSelect) {
            ordenSelect.addEventListener('change', function(e) {
                e.preventDefault();
                // No hacer nada - solo el botón aplica los filtros
            });
        }
        
        if (envioSelect) {
            envioSelect.addEventListener('change', function(e) {
                e.preventDefault();
                // No hacer nada - solo el botón aplica los filtros
            });
        }
    });

    // Quantity controls
    document.querySelectorAll('.minus-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const input = this.nextElementSibling;
        const max = parseInt(input.getAttribute('max')) || 999;
        const currentValue = parseInt(input.value) || 1;
        if(currentValue > 1) {
          input.value = currentValue - 1;
        }
      });
    });

    document.querySelectorAll('.plus-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const max = parseInt(input.getAttribute('max')) || 999;
        const currentValue = parseInt(input.value) || 1;
        if(currentValue < max) {
          input.value = currentValue + 1;
        }
      });
    });

    // Add to cart - ACTUALIZADO CON VALIDACIÓN DE STOCK
    document.querySelectorAll('.add-cart-btn:not(.btn-agotado)').forEach(btn => {
      btn.addEventListener('click', function() {
        const id_producto = this.dataset.id;
        const qtyInput = this.parentElement.querySelector('.qty-input');
        const cantidad = parseInt(qtyInput.value);
        const stock = parseInt(qtyInput.getAttribute('max'));
        const productoNombre = this.closest('.product-card').querySelector('.product-title').textContent;
        
        // Validación adicional de stock
        if (cantidad > stock) {
          showNotification(`No hay suficiente stock disponible. Solo quedan ${stock} unidades de ${productoNombre}`, 'error');
          qtyInput.value = Math.min(cantidad, stock);
          return;
        }
        
        if (cantidad <= 0) {
          showNotification('La cantidad debe ser mayor a 0', 'error');
          return;
        }
        
        // Animación visual
        this.style.transform = 'scale(0.8)';
        setTimeout(() => {
          this.style.transform = 'scale(1.05)';
          setTimeout(() => {
            this.style.transform = 'scale(1)';
          }, 200);
        }, 100);
        
        fetch('ajax_carrito.php', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: 'id_producto=' + encodeURIComponent(id_producto) + '&cantidad=' + encodeURIComponent(cantidad)
        })
        .then(res => res.json())
        .then(data => {
          if(data.success){
            const cartCount = document.querySelector('.cart-count');
            cartCount.textContent = data.total;
            
            // Actualizar visualización de stock si está disponible en la respuesta
            if (data.stock_actual !== undefined) {
              const stockBadge = this.closest('.product-card').querySelector('.stock-badge');
              const stockInfo = this.closest('.product-card').querySelector('.stock-info');
              
              if (stockBadge) {
                if (data.stock_actual <= 5) {
                  stockBadge.className = 'stock-badge stock-bajo';
                  stockBadge.innerHTML = data.stock_actual <= 0 ? 'Agotado' : `¡Últimas ${data.stock_actual}!`;
                } else {
                  stockBadge.className = 'stock-badge stock-disponible';
                  stockBadge.innerHTML = `En Stock (${data.stock_actual})`;
                }
              }
              
              if (stockInfo) {
                stockInfo.innerHTML = `<i class="fas fa-box"></i> ${data.stock_actual} unidades disponibles`;
              }
              
              // Actualizar el máximo del input de cantidad
              qtyInput.setAttribute('max', data.stock_actual);
              if (parseInt(qtyInput.value) > data.stock_actual) {
                qtyInput.value = data.stock_actual;
              }
            }
            
            // Efecto visual en el carrito flotante
            const floatingCart = document.querySelector('.floating-cart');
            floatingCart.style.transform = 'scale(1.2)';
            setTimeout(() => {
              floatingCart.style.transform = 'scale(1)';
            }, 300);
            
            // Notificación futurista
            showNotification(data.message || 'Producto agregado al carrito');
          } else {
            showNotification(data.error || 'Error al agregar producto', 'error');
            
            // Actualizar interfaz si hay información de stock
            if (data.stock_disponible !== undefined) {
              qtyInput.setAttribute('max', data.stock_disponible);
              if (parseInt(qtyInput.value) > data.stock_disponible) {
                qtyInput.value = data.stock_disponible;
              }
              
              // Actualizar badge de stock
              const stockBadge = this.closest('.product-card').querySelector('.stock-badge');
              if (stockBadge) {
                if (data.stock_disponible <= 5) {
                  stockBadge.className = 'stock-badge stock-bajo';
                  stockBadge.innerHTML = data.stock_disponible <= 0 ? 'Agotado' : `¡Últimas ${data.stock_disponible}!`;
                }
              }
            }
          }
        })
        .catch(err => {
          console.error(err);
          showNotification('Error de conexión', 'error');
        });
      });
    });

    // Notificación futurista
    function showNotification(message, type = 'success') {
      const notification = document.createElement('div');
      const bgColor = type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)';
      
      notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 25px;
        background: ${bgColor};
        color: white;
        border-radius: 12px;
        font-weight: 600;
        z-index: 10000;
        animation: slideInRight 0.5s ease;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        font-size: 14px;
        max-width: 300px;
      `;
      notification.textContent = message;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => {
          notification.remove();
        }, 500);
      }, 3000);
    }

    // Header scroll effect
    window.addEventListener('scroll', () => {
      const header = document.querySelector('.commercial-header');
      if(window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // Mobile menu functionality
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const commercialNav = document.querySelector('.commercial-nav');
    
    if (mobileMenuBtn && commercialNav) {
      mobileMenuBtn.addEventListener('click', () => {
        const isVisible = commercialNav.style.display === 'flex';
        commercialNav.style.display = isVisible ? 'none' : 'flex';
        if (!isVisible) {
          commercialNav.style.flexDirection = 'column';
          commercialNav.style.position = 'absolute';
          commercialNav.style.top = '100%';
          commercialNav.style.left = '0';
          commercialNav.style.right = '0';
          commercialNav.style.background = 'rgba(30, 41, 59, 0.98)';
          commercialNav.style.backdropFilter = 'blur(10px)';
          commercialNav.style.padding = '20px';
          commercialNav.style.gap = '15px';
        }
      });

      // Cerrar menú al hacer clic fuera
      document.addEventListener('click', (e) => {
        // Solo cerrar el menú si el click no es en el menú ni en el botón de menú móvil
        // y ademas estamos en vista móvil
        if (!commercialNav.contains(e.target) && !mobileMenuBtn.contains(e.target) && window.innerWidth <= 991) {
          commercialNav.style.display = 'none';
        }
      });
    }

    // Add animation keyframes
    const style = document.createElement('style');
    style.textContent = `
      @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    `;
    document.head.appendChild(style);

    // Ajustar grid de productos según tamaño de pantalla
    function adjustProductGrid() {
      const productsGrid = document.querySelector('.products-grid');
      if (productsGrid) {
        const width = window.innerWidth;
        if (width >= 1920) {
          productsGrid.style.gridTemplateColumns = 'repeat(5, 1fr)';
        } else if (width >= 1200) {
          productsGrid.style.gridTemplateColumns = 'repeat(4, 1fr)';
        } else if (width >= 992) {
          productsGrid.style.gridTemplateColumns = 'repeat(3, 1fr)';
        } else if (width >= 768) {
          productsGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        } else {
          productsGrid.style.gridTemplateColumns = '1fr';
        }
      }
    }

    // Ajustar inicialmente y en redimensionamiento
    adjustProductGrid();
    window.addEventListener('resize', adjustProductGrid);
  </script>
  
  <!-- Quick Chat Icon -->
  <div class="chat-icon" id="chatIcon">
    <i class="fas fa-comments"></i>
    <span class="notification-dot"></span>
  </div>

  <!-- Chatbot Widget -->
  <div class="chatbot-widget" id="chatbotWidget">
    <div class="chat-header">
      <h3>Asistente Virtual</h3>
      <span class="close-btn" id="closeBtn">&times;</span>
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="message bot-message">
        <i class="fas fa-robot"></i>
        <p>¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?</p>
      </div>
    </div>
    <div class="chat-input">
      <input type="text" id="userInput" placeholder="Escribe tu mensaje aquí...">
      <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>

  <style>
    /* Chat Icon Styles */
    .chat-icon {
      position: fixed;
      bottom: 30px;
      right: 110px;  /* Ajustado para posicionarlo al lado del cart (30 + 70 + 10 de espacio) */
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
      z-index: 1000;
      transition: all 0.3s ease;
    }

    .chat-icon:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
    }

    .notification-dot {
      position: absolute;
      top: -5px;
      right: -5px;
      width: 18px;
      height: 18px;
      background-color: #ef4444;
      border-radius: 50%;
      display: block;
    }

    /* Chatbot Widget Styles */
    .chatbot-widget {
      position: fixed;
      bottom: 100px;
      right: 100px;  /* Ajustado para alinearse con el icono del chat */
      width: 350px;
      height: 500px;
      background: var(--card-bg);
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      z-index: 1000;
      overflow: hidden;
      transform: translateY(20px);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .chatbot-widget.active {
      transform: translateY(0);
      opacity: 1;
      visibility: visible;
    }

    .chat-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .chat-header h3 {
      font-size: 1.2rem;
      font-weight: 600;
    }

    .close-btn {
      font-size: 1.5rem;
      cursor: pointer;
      transition: transform 0.2s;
    }

    .close-btn:hover {
      transform: scale(1.2);
    }

    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 15px;
      background-color: var(--bg-color);
    }

    .message {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      max-width: 85%;
    }

    .message.bot-message {
      align-self: flex-start;
    }

    .message.user-message {
      align-self: flex-end;
      flex-direction: row-reverse;
    }

    .message i {
      font-size: 1.2rem;
      margin-top: 5px;
    }

    .bot-message i {
      color: var(--primary);
    }

    .user-message i {
      color: #3b82f6;
    }

    .message p {
      background: white;
      padding: 12px 15px;
      border-radius: 18px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
      margin: 0;
    }

    .bot-message p {
      background: #e0f2fe;
      border-bottom-left-radius: 5px;
    }

    .user-message p {
      background: #d1fae5;
      border-bottom-right-radius: 5px;
    }

    .chat-input {
      display: flex;
      padding: 15px;
      background: var(--card-bg);
      border-top: 1px solid var(--glass-border);
    }

    .chat-input input {
      flex: 1;
      padding: 12px 15px;
      border: 1px solid var(--glass-border);
      border-radius: 25px;
      outline: none;
      font-size: 1rem;
      background: var(--glass-bg);
    }

    .chat-input input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }

    .chat-input button {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      width: 45px;
      height: 45px;
      border-radius: 50%;
      margin-left: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .chat-input button:hover {
      transform: scale(1.05);
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .chatbot-widget {
        width: 300px;
        right: 95px; /* Ajustado para alinearse con el icono del chat */
        bottom: 90px;
      }
      
      .chat-icon {
        bottom: 20px;
        right: 100px; /* Ajustado para posicionarlo al lado del cart */
        width: 55px;
        height: 55px;
      }
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Obtiene elementos del DOM
      const chatIcon = document.getElementById('chatIcon');
      const chatbotWidget = document.getElementById('chatbotWidget');
      const closeBtn = document.getElementById('closeBtn');
      const chatMessages = document.getElementById('chatMessages');
      const userInput = document.getElementById('userInput');
      const sendBtn = document.getElementById('sendBtn');
      
      // Alternar visibilidad del chatbot al hacer clic en el icono
      chatIcon.addEventListener('click', function() {
        chatbotWidget.classList.toggle('active');
        
        // Eliminar punto de notificación cuando se abre el chat
        const notificationDot = document.querySelector('.notification-dot');
        if (notificationDot) {
          notificationDot.style.display = 'none';
        }
      });
      
      // Cerrar chatbot al hacer clic en el botón de cerrar
      closeBtn.addEventListener('click', function() {
        chatbotWidget.classList.remove('active');
      });
      
      // Enviar mensaje al hacer clic en el botón de enviar
      sendBtn.addEventListener('click', sendMessage);
      
      // Enviar mensaje al presionar la tecla Enter
      userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          sendMessage();
        }
      });
      
      // Función para enviar y mostrar mensajes
      function sendMessage() {
        const message = userInput.value.trim();
        
        if (message) {
          // Agregar mensaje del usuario al chat
          addMessageToChat(message, 'user');
          
          // Limpiar campo de entrada
          userInput.value = '';
          
          // Simular respuesta del bot
          setTimeout(() => {
            getBotResponse(message);
          }, 500);
        }
      }
      
      // Función para agregar mensajes al chat
      function addMessageToChat(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        
        if (sender === 'user') {
          messageDiv.classList.add('user-message');
          messageDiv.innerHTML = `
            <i class="fas fa-user"></i>
            <p>${message}</p>
          `;
        } else {
          messageDiv.classList.add('bot-message');
          messageDiv.innerHTML = `
            <i class="fas fa-robot"></i>
            <p>${message}</p>
          `;
        }
        
        chatMessages.appendChild(messageDiv);
        
        // Desplazarse al fondo del chat
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
      
      // Función para obtener respuesta del bot
      function getBotResponse(message) {
        // Convertir mensaje a minúsculas para coincidencia y limpiar caracteres especiales
        let lowerMessage = message.toLowerCase();
        // Remover caracteres especiales y reemplazar con espacios para mejorar la detección de palabras
        lowerMessage = lowerMessage.replace(/[^\w\s]/g, ' ');
        
        // Definir respuestas básicas
        let response = "Lo siento, no entiendo tu mensaje. ¿Podrías reformularlo o preguntarme algo sobre nuestros productos de salud?";
        
        // Definir diccionario de palabras clave y respuestas
        const responses = {
          // Saludos y cortesías
          'hola': "¡Hola! Soy tu asistente de salud. ¿En qué puedo ayudarte hoy?",
          'buenos dias': "¡Buenos días! ¿En qué puedo ayudarte con tu salud hoy?",
          'buenas tardes': "¡Buenas tardes! ¿Tienes alguna consulta sobre medicamentos o síntomas?",
          'buenas noches': "¡Buenas noches! Estoy aquí para ayudarte con tus consultas de salud.",
          'como estas': "¡Estoy bien, gracias! Listo para ayudarte con tus consultas de salud. ¿En qué te puedo asistir?",
          'adios': "¡Cuídate! Recuerda consultar siempre con un profesional de la salud si los síntomas persisten.",
          'gracias': "De nada. ¡Tu salud es lo más importante! No dudes en preguntar si necesitas más ayuda.",
          'por favor': "¡Claro! Estoy aquí para ayudarte. ¿Qué necesitas saber?",
          
          // Síntomas comunes
          'dolor de cabeza': "El dolor de cabeza puede tener varias causas. El paracetamol o ibuprofeno pueden ayudar, pero si es muy intenso o persistente, consulta a un médico.",
          'migraña': "El dolor de cabeza puede tener varias causas. El paracetamol o ibuprofeno pueden ayudar, pero si es muy intenso o persistente, consulta a un médico.",
          'migrana': "El dolor de cabeza puede tener varias causas. El paracetamol o ibuprofeno pueden ayudar, pero si es muy intenso o persistente, consulta a un médico.",
          'jaqueca': "El dolor de cabeza puede tener varias causas. El paracetamol o ibuprofeno pueden ayudar, pero si es muy intenso o persistente, consulta a un médico.",
          'fiebre': "La fiebre es un síntoma común de infección. El paracetamol puede ayudar a reducirla. Si supera los 39°C o dura más de 3 días, busca atención médica.",
          'gripe': "Para la gripe, descansa, bebe líquidos y puedes tomar paracetamol para el malestar. Si tienes dificultad para respirar, busca ayuda médica.",
          'resfriado': "El resfriado común mejora con descanso e hidratación. Los descongestionantes pueden aliviar la congestión nasal.",
          'tos': "La tos puede ser seca o con flemas. Los jarabes para la tos pueden ayudar, pero si persiste por más de una semana, consulta a un farmacéutico o médico.",
          'dolor de garganta': "Las gárgaras con agua tibia y sal pueden aliviar. También hay pastillas específicas. Si el dolor es intenso o hay fiebre, consulta a un médico.",
          'congestion nasal': "Los descongestionantes nasales en spray pueden proporcionar alivio, pero no los uses por más de 3-5 días consecutivos.",
          'congestión nasal': "Los descongestionantes nasales en spray pueden proporcionar alivio, pero no los uses por más de 3-5 días consecutivos.",
          'dolor muscular': "Para dolores musculares leves, el ibuprofeno puede ser efectivo. Aplicar hielo y reposar también ayuda.",
          'dolor de espalda': "El reposo relativo y antiinflamatorios como el ibuprofeno pueden aliviar el dolor de espalda. Si el dolor es irradiado o muy intenso, consulta a un médico.",
          'calambre': "Los calambres suelen deberse a deshidratación o esfuerzo. Estirar suavemente y mantenerse hidratado ayuda a prevenirlos.",
          'acidez': "Los antiácidos de venta libre pueden aliviar la acidez ocasional. Si es frecuente, es importante consultar a un médico.",
          'indigestion': "Para la indigestión, evita comidas pesadas y considera antiácidos. Si los síntomas son recurrentes, busca consejo médico.",
          'indigestión': "Para la indigestión, evita comidas pesadas y considera antiácidos. Si los síntomas son recurrentes, busca consejo médico.",
          'diarrea': "Mantén una buena hidratación. Puedes usar sales de rehidratación oral. Si la diarrea persiste más de 2 días o hay fiebre, consulta a un médico.",
          'estrenimiento': "Aumenta el consumo de fibra y agua. Los laxantes suaves pueden ayudar, pero no los uses de forma prolongada sin supervisión.",
          'nauseas': "Las náuseas leves pueden aliviarse con reposo y comidas ligeras. Si son persistentes o van acompañadas de vómitos, consulta a un médico.",
          'náuseas': "Las náuseas leves pueden aliviarse con reposo y comidas ligeras. Si son persistentes o van acompañadas de vómitos, consulta a un médico.",
          'vomitos': "Mantén la hidratación con pequeños sorbos de agua. Si los vómitos son persistentes o hay señales de deshidratación, busca atención médica urgente.",
          'vómitos': "Mantén la hidratación con pequeños sorbos de agua. Si los vómitos son persistentes o hay señales de deshidratación, busca atención médica urgente.",
          'mareos': "Los mareos pueden deberse a muchas causas. Si son recurrentes o acompañados de otros síntomas, es importante una evaluación médica.",
          'cansancio': "El cansancio persistente puede relacionarse con falta de sueño, estrés o condiciones médicas. Si es severo, consulta con un profesional.",
          'fatiga': "El cansancio persistente puede relacionarse con falta de sueño, estrés o condiciones médicas. Si es severo, consulta con un profesional.",
          
          // Medicamentos comunes
          'paracetamol': "El paracetamol es un medicamento usado para aliviar el dolor leve o moderado y reducir la fiebre. Se recomienda seguir las dosis indicadas y no exceder la cantidad diaria máxima. Ante síntomas persistentes, consulta con un médico o farmacéutico.",
          'ibuprofeno': "El ibuprofeno es un antiinflamatorio no esteroideo (AINE) para el dolor, la inflamación y la fiebre. Puede irritar el estómago, por lo que se recomienda tomarlo con comida. No excedas la dosis diaria.",
          'aspirina': "La aspirina (ácido acetilsalicílico) se usa para el dolor, la fiebre y la inflamación. No se recomienda en niños por el riesgo de síndrome de Reye. Consulta a un médico antes de usarla regularmente.",
          'ácido acetilsalicílico': "La aspirina (ácido acetilsalicílico) se usa para el dolor, la fiebre y la inflamación. No se recomienda en niños por el riesgo de síndrome de Reye. Consulta a un médico antes de usarla regularmente.",
          'naproxeno': "El naproxeno es otro antiinflamatorio para el dolor. Sigue las instrucciones de dosificación cuidadosamente.",
          'diclofenaco': "El diclofenaco es un antiinflamatorio potente. Está disponible en varias formas (gel, comprimidos). Usa la dosis efectiva más baja durante el menor tiempo posible.",
          'omeprazol': "El omeprazol reduce la producción de ácido estomacal. Se usa para la acidez y la protección gástrica. No se recomienda su uso prolongado sin supervisión médica.",
          'antiacido': "Los antiácidos neutralizan el ácido estomacal y alivian la acidez ocasional. Tómalos según las instrucciones del envase.",
          'antiácido': "Los antiácidos neutralizan el ácido estomacal y alivian la acidez ocasional. Tómalos según las instrucciones del envase.",
          'laxante': "Los laxantes ayudan con el estreñimiento. Prioriza los de origen natural o formadores de volumen. El uso excesivo puede crear dependencia.",
          'sales de rehidratacion oral': "Las sales de rehidratación oral son esenciales para recuperar líquidos y electrolitos en casos de diarrea o vómitos.",
          'loratadina': "La loratadina es un antihistamínico no sedante para alergias como la rinitis alérgica o la urticaria. Alivia síntomas como estornudos y picazón.",
          'cetirizina': "La cetirizina es un antihistamínico para alergias. En algunas personas puede causar somnolencia.",
          'desloratadina': "La desloratadina es un antihistamínico similar a la loratadina, usado para el alivio de los síntomas de la rinitis alérgica.",
          'jarabe para la tos': "Existen jarabes para la tos seca y para la tos productiva. Es importante elegir el adecuado para tu tipo de tos. Pregunta a tu farmacéutico.",
          'descongestionante nasal': "Los descongestionantes nasales en spray alivian la congestión rápidamente. No los uses por más de 3-5 días para evitar el efecto rebote.",
          'pastillas para la garganta': "Las pastillas o caramelos para la garganta pueden aliviar temporalmente el dolor y la irritación. Algunas contienen anestésicos locales o antisépticos.",
          'crema para hemorroides': "Las cremas para hemorroides suelen contener ingredientes que alivian el picor, el dolor y la inflamación. Sigue las instrucciones de aplicación.",
          'crema para hongos': "Las cremas antimicóticas se usan para infecciones por hongos en la piel. Aplica según las indicaciones y completa el tratamiento incluso si los síntomas desaparecen.",
          'vitamina c': "La vitamina C es un antioxidante importante para el sistema inmunológico. Se encuentra en frutas cítricas y suplementos.",
          'vitamina d': "La vitamina D es crucial para la salud ósea y el sistema inmunológico. La principal fuente es la exposición solar segura.",
          'suplemento de hierro': "Los suplementos de hierro se usan para tratar o prevenir la anemia. Pueden causar estreñimiento y se absorven mejor con vitamina C.",
          'probiotico': "Los probióticos ayudan a restaurar el equilibrio de la flora intestinal. Son útiles durante y después de un tratamiento con antibióticos o para problemas digestivos.",
          'probiótico': "Los probióticos ayudan a restaurar el equilibrio de la flora intestinal. Son útiles durante y después de un tratamiento con antibióticos o para problemas digestivos.",
          
          // Condiciones de salud
          'presion arterial alta': "La hipertensión arterial requiere un manejo constante que incluye medicación, dieta y ejercicio. Sigue siempre las indicaciones de tu médico.",
          'presión arterial alta': "La hipertensión arterial requiere un manejo constante que incluye medicación, dieta y ejercicio. Sigue siempre las indicaciones de tu médico.",
          'hipertension': "La hipertensión arterial requiere un manejo constante que incluye medicación, dieta y ejercicio. Sigue siempre las indicaciones de tu médico.",
          'hipertensión': "La hipertensión arterial requiere un manejo constante que incluye medicación, dieta y ejercicio. Sigue siempre las indicaciones de tu médico.",
          'colesterol alto': "Para controlar el colesterol alto son clave los cambios en la dieta, el ejercicio y, si el médico lo indica, medicación como las estatinas.",
          'diabetes': "El manejo de la diabetes implica control de la glucemia, dieta, ejercicio y, a menudo, medicación o insulina. Sigue rigurosamente el plan de tu médico.",
          'ansiedad': "La ansiedad puede manejarse con terapia, técnicas de relajación y, en algunos casos, medicación. Busca ayuda profesional para un diagnóstico y tratamiento adecuados.",
          'estres': "El estrés crónico puede afectar la salud. Técnicas como el mindfulness, el ejercicio y un sueño adecuado son fundamentales para manejarlo.",
          'estrés': "El estrés crónico puede afectar la salud. Técnicas como el mindfulness, el ejercicio y un sueño adecuado son fundamentales para manejarlo.",
          'insomnio': "Para el insomnio, prioriza la higiene del sueño (rutina, ambiente oscuro y fresco). Los medicamentos para dormir deben usarse solo bajo prescripción médica.",
          'dolor menstrual': "Para los cólicos menstruales, el ibuprofeno suele ser efectivo. El calor local también puede proporcionar alivio.",
          'infeccion urinaria': "Las infecciones urinarias suelen requerir antibióticos. Es importante beber mucha agua y consultar a un médico para un tratamiento adecuado.",
          'infección urinaria': "Las infecciones urinarias suelen requerir antibióticos. Es importante beber mucha agua y consultar a un médico para un tratamiento adecuado.",
          'quemadura solar': "Para las quemaduras solares leves, aplica aloe vera o cremas hidratantes. El paracetamol o ibuprofeno pueden ayudar con el dolor. En quemaduras graves, busca ayuda médica.",
          
          // Uso seguro de medicamentos
          'dosis': "La dosis correcta depende del medicamento, la edad y el peso. Nunca excedas la dosis recomendada en el envase o por tu médico.",
          'efectos secundarios': "Todos los medicamentos pueden tener efectos secundarios. Lee el prospecto y consulta a tu farmacéutico o médico si experimentas algo inusual.",
          'interacciones': "Algunos medicamentos pueden interactuar entre sí, con suplementos o con alimentos. Informa siempre a tu médico sobre todos los medicamentos que tomas.",
          'puedo tomar alcohol con esto': "El alcohol puede interactuar con muchos medicamentos, aumentando los efectos secundarios o reduciendo su eficacia. Es mejor evitarlo durante el tratamiento. Consulta el prospecto o a tu farmacéutico.",
          'con o sin comida': "Algunos medicamentos se toman con comida para evitar molestias estomacales (como el ibuprofeno), otros en ayunas para una mejor absorción (como el omeprazol). Sigue las instrucciones específicas.",
          'caducidad': "No uses medicamentos caducados. Pueden haber perdido eficacia o, en algunos casos, volverse perjudiciales.",
          'vencimiento': "No uses medicamentos caducados. Pueden haber perdido eficacia o, en algunos casos, volverse perjudiciales.",
          'medicamento generico': "Los medicamentos genéricos contienen el mismo principio activo, en la misma dosis y forma farmacéutica que el medicamento de marca original. Son igual de seguros y eficaces, y suelen tener un coste menor.",
          'medicamento genérico': "Los medicamentos genéricos contienen el mismo principio activo, en la misma dosis y forma farmacéutica que el medicamento de marca original. Son igual de seguros y eficaces, y suelen tener un coste menor.",
          'se necesita receta': "Algunos medicamentos, como los antibióticos o los psicotrópicos, requieren receta médica para garantizar un uso seguro y adecuado.",
          'requiere receta': "Algunos medicamentos, como los antibióticos o los psicotrópicos, requieren receta médica para garantizar un uso seguro y adecuado.",
          
          // Primeros auxilios
          'cortadura': "Lava la herida con agua y jabón suave. Aplica un antiséptico y cúbrela con una gasa estéril o una curita para prevenir infecciones.",
          'herida': "Lava la herida con agua y jabón suave. Aplica un antiséptico y cúbrela con una gasa estéril o una curita para prevenir infecciones.",
          'quemadura': "Para quemaduras leves, enfría la zona con agua corriente (no hielo) durante varios minutos. Cubre con una gasa estéril sin apretar. No apliques pasta de dientes, mantequilla u otros remedios caseros.",
          'esguince': "Para un esguince, aplica el protocolo R.H.E.D.: Reposo, Hielo, Elevación y Compresión (con un vendaje). Consulta a un médico para una evaluación.",
          'picadura de insecto': "Lava la zona con agua y jabón. Aplica hielo para reducir la hinchazón y una crema con hidrocortisona para el picor. Vigila si aparecen signos de reacción alérgica grave (dificultad para respirar, hinchazón de cara/boca).",
          'reaccion alergica': "Si experimentas dificultad para respirar, hinchazón de labios, lengua o garganta, o mareo intenso, busca atención médica de URGENCIA. Podría ser una anafilaxia.",
          'reacción alérgica': "Si experimentas dificultad para respirar, hinchazón de labios, lengua o garganta, o mareo intenso, busca atención médica de URGENCIA. Podría ser una anafilaxia.",
          'atragantamiento': "Si la persona puede toser, anímala a seguir tosiendo. Si no puede toser, hablar o respirar, realiza la maniobra de Heimlich inmediatamente y pide ayuda de emergencia.",
          'ahogamiento': "Si la persona puede toser, anímala a seguir tosiendo. Si no puede toser, hablar o respirar, realiza la maniobra de Heimlich inmediatamente y pide ayuda de emergencia.",
          
          // Salud infantil
          'fiebre en ninos': "En niños, la fiebre es común. Usa paracetamol o ibuprofeno pediátrico según el peso y las indicaciones. Si el niño está muy decaído, tiene dificultad para respirar o la fiebre es muy alta, busca ayuda médica.",
          'fiebre en niños': "En niños, la fiebre es común. Usa paracetamol o ibuprofeno pediátrico según el peso y las indicaciones. Si el niño está muy decaído, tiene dificultad para respirar o la fiebre es muy alta, busca ayuda médica.",
          'dosis para ninos': "La dosis en niños se calcula casi siempre por peso. Usa siempre el medidor que viene con el medicamento (jeringa, cuentagotas) y nunca una cuchara de casa.",
          'dosis para niños': "La dosis en niños se calcula casi siempre por peso. Usa siempre el medidor que viene con el medicamento (jeringa, cuentagotas) y nunca una cuchara de casa.",
          'vacunas': "Las vacunas son esenciales para proteger a los niños de enfermedades graves. Sigue el calendario de vacunación recomendado por las autoridades sanitarias.",
          'vacunación': "Las vacunas son esenciales para proteger a los niños de enfermedades graves. Sigue el calendario de vacunación recomendado por las autoridades sanitarias.",
          
          // Servicios y generales
          'ayuda': "Puedo ayudarte con información sobre nuestros productos de salud, horarios de atención, o cualquier duda que tengas.",
          'ayudame': "Puedo ayudarte con información sobre nuestros productos de salud, horarios de atención, o cualquier duda que tengas.",
          'ayúdame': "Puedo ayudarte con información sobre nuestros productos de salud, horarios de atención, o cualquier duda que tengas.",
          'productos': "Ofrecemos una amplia variedad de productos de salud y bienestar como suplementos inmunológicos, vitaminas, minerales y productos para el bienestar diario.",
          'producto': "Ofrecemos una amplia variedad de productos de salud y bienestar como suplementos inmunológicos, vitaminas, minerales y productos para el bienestar diario.",
          'contacto': "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'telefono': "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'teléfono': "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'horario': "Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 horas.",
          'atencion': "Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 horas.",
          'atención': "Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 horas.",
          'precio': "Los precios de nuestros productos varían según el tipo y marca. Puedes ver los precios detallados en la sección de productos.",
          'costo': "Los precios de nuestros productos varían según el tipo y marca. Puedes ver los precios detallados en la sección de productos.",
          'envío': "Ofrecemos envíos a nivel nacional con tiempos de entrega de 24 a 48 horas hábiles.",
          'envio': "Ofrecemos envíos a nivel nacional con tiempos de entrega de 24 a 48 horas hábiles.",
          'entrega': "Ofrecemos envíos a nivel nacional con tiempos de entrega de 24 a 48 horas hábiles.",
          'devolución': "Aceptamos devoluciones dentro de los 30 días siguientes a la compra, siempre que el producto esté en condiciones originales.",
          'devolucion': "Aceptamos devoluciones dentro de los 30 días siguientes a la compra, siempre que el producto esté en condiciones originales.",
          'devolver': "Aceptamos devoluciones dentro de los 30 días siguientes a la compra, siempre que el producto esté en condiciones originales.",
          'promoción': "Consulta nuestra sección de promociones para ver los productos con descuento actualmente disponibles.",
          'promocion': "Consulta nuestra sección de promociones para ver los productos con descuento actualmente disponibles.",
          'oferta': "Consulta nuestra sección de promociones para ver los productos con descuento actualmente disponibles.",
          'devoluciones': "Aceptamos devoluciones dentro de los 30 días siguientes a la compra, siempre que el producto esté en condiciones originales.",
          'reembolso': "Aceptamos devoluciones dentro de los 30 días siguientes a la compra, siempre que el producto esté en condiciones originales.",
          'atencion al cliente': "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'atención al cliente': "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'servicio al cliente': "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'ayuda con pedido': "Para consultas sobre tu pedido, puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'estado de pedido': "Para consultar el estado de tu pedido, puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'seguimiento': "Para consultar el estado de tu pedido, puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'tracking': "Para consultar el estado de tu pedido, puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
          'pago': "Aceptamos diversos métodos de pago, incluyendo tarjetas de crédito, débito y transferencia bancaria. Todos los pagos son seguros.",
          'metodos de pago': "Aceptamos diversos métodos de pago, incluyendo tarjetas de crédito, débito y transferencia bancaria. Todos los pagos son seguros.",
          'métodos de pago': "Aceptamos diversos métodos de pago, incluyendo tarjetas de crédito, débito y transferencia bancaria. Todos los pagos son seguros.",
          'seguridad': "Tus datos personales y de pago están completamente seguros con nosotros. Usamos encriptación SSL y seguimos estrictos protocolos de seguridad.",
          'privacidad': "Garantizamos la privacidad y protección de tus datos personales según la ley de protección de datos vigente.",
          'buenas dias': "¡Buenos días! ¿En qué puedo ayudarte con tu salud hoy?",
          'como estás': "¡Estoy bien, gracias! Listo para ayudarte con tus consultas de salud. ¿En qué te puedo asistir?",
          'adiós': "¡Cuídate! Recuerda consultar siempre con un profesional de la salud si los síntomas persisten.",
          'cuentame': "Puedo ayudarte con información sobre nuestros productos de salud, horarios de atención, o cualquier duda que tengas.",
          'para que sirve': "Para conocer para qué sirve un producto específico, puedes mencionar el nombre del producto para que te proporcione información detallada.",
          'informacion': "Puedo proporcionarte información sobre nuestros productos de salud, horarios de atención, o cualquier duda que tengas.",
          'detalles': "Puedo proporcionarte detalles sobre nuestros productos de salud, horarios de atención, o cualquier duda que tengas."
        };
        
        // Buscar coincidencias exactas en orden de longitud (de más largas a más cortas)
        // Esto asegura que coincidencias más específicas tengan prioridad
        const sortedKeys = Object.keys(responses).sort((a, b) => b.length - a.length);
        
        for (const key of sortedKeys) {
          if (lowerMessage.includes(key)) {
            response = responses[key];
            break;
          }
        }
        
        // Si no se encontró una coincidencia específica, verificar si el usuario está preguntando por un producto
        if (response.includes("no entiendo tu mensaje")) {
          // Verificar si el mensaje contiene palabras clave que indiquen que está buscando un producto
          const palabrasProducto = ['túnez', 'taladro', 'producto', '¿qué es', '¿qué es un', '¿qué es una', '¿qué es el', '¿qué es la', 'qué es', 'cuéntame', 'cuentame', 'información', 'informacion', 'sobre', 'describe', 'descripción', 'característica', 'características', 'para qué sirve', 'para que sirve', 'beneficios', 'efectos', 'efectos secundarios', 'ingredientes'];
          
          const tienePalabraProducto = palabrasProducto.some(palabra => lowerMessage.includes(palabra));
          
          if (tienePalabraProducto) {
            // Buscar si hay una palabra que podría ser un nombre de producto
            const palabras = lowerMessage.split(/\s+/);
            const palabrasComunes = ['un', 'una', 'el', 'la', 'de', 'que', 'es', 'y', 'cuéntame', 'cuentame', 'información', 'informacion', 'sobre', 'describe', 'qué', 'por', 'para', 'qué', 'es', 'un', 'una', 'el', 'la', 'en', 'al', 'con', 'le', 'les', 'se', 'me', 'te', 'le', 'nos', 'os'];
            
            let productoBuscado = '';
            for (const palabra of palabras) {
              if (!palabrasComunes.includes(palabra) && palabra.length > 2) {
                // Verificar si no es una palabra de pregunta
                if (!['qué', 'como', 'cuál', 'cual', 'cuáles', 'como', 'por', 'para', 'hasta', 'desde', 'mientras', 'cuando', 'donde', 'dónde', 'cuanto', 'cuánto', 'cuántos', 'cuantas', 'cuántas'].includes(palabra)) {
                  productoBuscado = palabra;
                  break;
                }
              }
            }
            
            if (productoBuscado) {
              // Si se encontró un posible nombre de producto, hacer la búsqueda
              buscarProducto(productoBuscado);
              return; // Salir de la función para que la búsqueda maneje la respuesta
            }
          }
        }
        
        addMessageToChat(response, 'bot');
      }
      
      // Función para buscar productos en la base de datos
      function buscarProducto(nombreProducto) {
        fetch('buscar_producto.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'producto_nombre=' + encodeURIComponent(nombreProducto)
        })
        .then(response => response.json())
        .then(data => {
          if (data.encontrado) {
            let productoInfo = "He encontrado los siguientes productos relacionados con '" + nombreProducto + "':\\n\\n";
            
            data.productos.forEach((producto, index) => {
              productoInfo += (index + 1) + ". " + producto.nombre + "\\n";
              productoInfo += "   Descripción: " + producto.descripcion + "\\n";
              
              // Formatear precios para mostrarlos adecuadamente
              let precioTexto = "";
              if (producto.precio_regular && producto.precio_venta && producto.precio_venta < producto.precio_regular) {
                precioTexto = "   Precio regular: S/ " + parseFloat(producto.precio_regular).toFixed(2) + 
                             " | Precio en oferta: S/ " + parseFloat(producto.precio_venta).toFixed(2) + "\\n";
              } else if (producto.precio_venta) {
                precioTexto = "   Precio: S/ " + parseFloat(producto.precio_venta).toFixed(2) + "\\n";
              } else if (producto.precio_regular) {
                precioTexto = "   Precio: S/ " + parseFloat(producto.precio_regular).toFixed(2) + "\\n";
              }
              
              productoInfo += precioTexto;
              productoInfo += "   Categoría: " + producto.categoria + "\\n";
              productoInfo += "   Stock disponible: " + producto.stock + " unidades\\n\\n";
            });
            
            addMessageToChat(productoInfo, 'bot');
          } else {
            addMessageToChat(data.mensaje, 'bot');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          addMessageToChat('Lo siento, hubo un error al buscar el producto. Por favor, inténtalo de nuevo más tarde.', 'bot');
        });
      }
    });
  </script>
</body>
</html>
<?php
// Cerrar conexión
if (isset($conexion)) {
    $conexion->close();
}
?>