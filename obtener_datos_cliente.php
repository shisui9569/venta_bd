<?php
session_start();
include 'conexion.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

$usuario = $_SESSION['usuario'];

// Buscar al cliente en la base de datos usando el correo electrónico
$stmt = $conexion->prepare("SELECT id_cliente, tipo_documento, nro_documento, nombres, apellidos, email, telefono, direccion, departamento, provincia, distrito FROM cliente WHERE email = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'cliente' => [
            'id_cliente' => $cliente['id_cliente'],
            'tipo_documento' => $cliente['tipo_documento'],
            'nro_documento' => $cliente['nro_documento'],
            'nombres' => $cliente['nombres'],
            'apellidos' => $cliente['apellidos'],
            'email' => $cliente['email'],
            'telefono' => $cliente['telefono'],
            'direccion' => $cliente['direccion'],
            'departamento' => $cliente['departamento'],
            'provincia' => $cliente['provincia'],
            'distrito' => $cliente['distrito']
        ]
    ]);
} else {
    // Si no se encuentra el cliente en la tabla cliente, probablemente es un usuario admin
    // que inició sesión en el panel de administración, pero no es un cliente regular
    echo json_encode([
        'success' => false,
        'message' => 'Cliente no encontrado',
        'cliente' => [
            'tipo_documento' => '',
            'nro_documento' => '',
            'nombres' => '',
            'apellidos' => '',
            'email' => $usuario,
            'telefono' => '',
            'direccion' => '',
            'departamento' => '',
            'provincia' => '',
            'distrito' => ''
        ]
    ]);
}
?>