<?php
session_start();
include 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que los campos existen antes de acceder a ellos
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $clave = isset($_POST['contraseña']) ? trim($_POST['contraseña']) : '';

    // Verificar que no estén vacíos
    if (empty($usuario) || empty($clave)) {
        $error = "❌ Por favor, complete todos los campos.";
    } else {
        // Primero buscar el usuario
        $stmt = $conexion->prepare("SELECT id_usuario, usuario, contraseña, rol, estado FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $user = $resultado->fetch_assoc();
            if ($user['estado'] == 0) {
                $error = "🚫 Tu cuenta está desactivada.";
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
                    header("Location: admin_productos.php");
                    exit;
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
                        header("Location: admin_productos.php");
                        exit;
                    } else {
                        $error = "❌ Usuario o contraseña incorrectos.";
                    }
                }
            }
        } else {
            $error = "❌ Usuario o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Open Sans", sans-serif;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
            padding: 0 10px;
            position: relative;
        }
        body::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('./imagenes/pueblo-en-hamnoy-noruega-al-atardecer_2560x1440_xtrafondos.com.jpg') no-repeat center center/cover;
            z-index: -1;
        }
        .wrapper {
            width: 400px;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(9px);
            -webkit-backdrop-filter: blur(9px);
        }
        form {
            display: flex;
            flex-direction: column;
        }
        h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #fff;
        }
        .input-field {
            position: relative;
            border-bottom: 2px solid #ccc;
            margin: 15px 0;
        }
        .input-field label {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            color: #fff;
            pointer-events: none;
            transition: 0.15s ease;
        }
        .input-field input {
            width: 100%;
            height: 40px;
            background: transparent;
            border: none;
            outline: none;
            font-size: 16px;
            color: #fff;
            padding-right: 35px;
        }
        .input-field input:focus ~ label,
        .input-field input:valid ~ label,
        .input-field.filled label {
            font-size: 0.8rem;
            top: 10px;
            transform: translateY(-120%);
        }
        .password-toggle {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        .password-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            opacity: 1;
        }
        .forget {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 25px 0 35px 0;
            color: #fff;
        }
        #remember {
            accent-color: #fff;
        }
        .forget label {
            display: flex;
            align-items: center;
        }
        .forget label p {
            margin-left: 8px;
        }
        .wrapper a {
            color: #efefef;
            text-decoration: none;
        }
        .wrapper a:hover {
            text-decoration: underline;
        }
        button[type="submit"] {
            background: #fff;
            color: #000;
            font-weight: 600;
            border: none;
            padding: 12px 0;
            cursor: pointer;
            border-radius: 3px;
            font-size: 16px;
            border: 2px solid transparent;
            transition: 0.3s ease;
            width: 100%;
        }
        button[type="submit"]:hover {
            color: #fff;
            border-color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }
        .register {
            text-align: center;
            margin-top: 30px;
            color: #fff;
        }
        .error {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 107, 107, 0.3);
            text-align: center;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <form method="post">
        <h2>Inicio de Sesión</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="credential-hint" style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin-bottom: 15px; color: #fff; font-size: 0.9rem;">
            <strong>Credenciales de prueba:</strong><br>
            Correo: admin@saludperfecta.com<br>
            Contraseña: login1234
        </div>
        
            <div class="input-field">
            <input type="email" name="usuario" required value="<?= isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : '' ?>">
            <label>Introduzca correo electrónico</label>
        </div>
        <div class="input-field">
            <input type="password" name="contraseña" id="contraseña" required>
            <label>Introduzca contraseña</label>
            <button type="button" class="password-toggle" onclick="togglePassword()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
            </button>
        </div>
        <div class="forget">
            <label for="remember">
                <input type="checkbox" id="remember">
                <p>Recordar usuario</p>
            </label>
            <a href="#">¿Olvidó su contraseña?</a>
        </div>
        <button type="submit">Iniciar Sesión</button>
        <div class="register">
            <p>¿No tiene una cuenta? <a href="registro.php">Registrarse</a></p>
        </div>
    </form>
</div>

<script>
    // JavaScript para mostrar/ocultar contraseña
    function togglePassword() {
        const passwordInput = document.getElementById('contraseña');
        const toggleButton = document.querySelector('.password-toggle');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
        } else {
            passwordInput.type = 'password';
            toggleButton.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
        }
    }

    // JavaScript para mantener las etiquetas flotantes cuando hay contenido
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.input-field input');
        
        inputs.forEach(input => {
            // Verificar si el input ya tiene valor (útil si el navegador autocompleta)
            if (input.value) {
                input.parentElement.classList.add('filled');
            }
            
            input.addEventListener('input', function() {
                if (this.value) {
                    this.parentElement.classList.add('filled');
                } else {
                    this.parentElement.classList.remove('filled');
                }
            });
        });
    });
</script>
</body>
</html>