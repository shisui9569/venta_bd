<?php
session_start();
include 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que los campos existen antes de acceder a ellos
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $clave = isset($_POST['contraseña']) ? trim($_POST['contraseña']) : '';

    // Verificar que no estén vacíos
    if (empty($usuario) || empty($clave)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, complete todos los campos.'
        ]);
        exit;
    }

    // Primero buscar el usuario
    $stmt = $conexion->prepare("SELECT id_usuario, usuario, contraseña, rol, estado FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();
        if ($user['estado'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tu cuenta está desactivada.'
            ]);
        } else {
            // Verificar la contraseña - soporte para contraseñas antiguas y nuevas
            $contraseña_almacenada = $user['contraseña'];
            
            // Comprobar si es una contraseña con hash (nueva) o texto plano (antigua)
            if (password_verify($clave, $contraseña_almacenada)) {
                // Contraseña correcta (nueva forma con hash)
                $_SESSION['logueado'] = true;
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['last_activity'] = time(); // Para control de inactividad
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión iniciada correctamente',
                    'usuario' => $user['usuario']
                ]);
            } else {
                // Comprobar si es una contraseña antigua (con \r\n)
                $clave_antigua = $clave . "\r\n";
                if ($clave_antigua === $contraseña_almacenada) {
                    // Contraseña correcta (antigua forma con texto plano y \r\n)
                    $_SESSION['logueado'] = true;
                    $_SESSION['id_usuario'] = $user['id_usuario'];
                    $_SESSION['usuario'] = $user['usuario'];
                    $_SESSION['rol'] = $user['rol'];
                    $_SESSION['last_activity'] = time(); // Para control de inactividad
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Sesión iniciada correctamente',
                        'usuario' => $user['usuario']
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Usuario o contraseña incorrectos.'
                    ]);
                }
            }
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>