<?php
session_start();
include 'conexion.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';
    $confirmar_contraseña = isset($_POST['confirmar_contraseña']) ? $_POST['confirmar_contraseña'] : '';
    $rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'usuario'; // Por defecto 'usuario'

    // Validaciones
    if (empty($correo) || empty($contraseña) || empty($confirmar_contraseña)) {
        $error = "❌ Por favor, complete todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El correo electrónico no es válido.";
    } elseif ($contraseña !== $confirmar_contraseña) {
        $error = "❌ Las contraseñas no coinciden.";
    } elseif (strlen($contraseña) < 6) {
        $error = "❌ La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el correo ya existe
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "❌ El correo electrónico ya está registrado.";
        } else {
            // Hash de la contraseña
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario (usando el correo como nombre de usuario)
            $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, contraseña, rol, estado) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $correo, $contraseña_hash, $rol);
            
            if ($stmt->execute()) {
                $success = "✅ Usuario registrado exitosamente. Puede iniciar sesión ahora.";
            } else {
                $error = "❌ Error al registrar el usuario. Inténtelo de nuevo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
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
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('./imagenes/pueblo-en-hamnoy-noruega-al-atardecer_2560x1440_xtrafondos.com.jpg') no-repeat center center/cover;
        }
        .wrapper {
            width: 400px;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(9px);
            -webkit-backdrop-filter: blur(9px);
            background: rgba(0, 0, 0, 0.3);
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
        .input-field input, .input-field select {
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
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            color: #fff;
            border-color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
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
        .success {
            color: #4caf50;
            background: rgba(76, 175, 80, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid rgba(76, 175, 80, 0.3);
            text-align: center;
        }
        .form-switch {
            margin-top: 15px;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <form method="post">
        <h2>Registro de Usuario</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="input-field">
            <input type="email" name="correo" required value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>">
            <label>Correo electrónico</label>
        </div>
        <div class="input-field">
            <input type="password" name="contraseña" id="contraseña" required>
            <label>Contraseña</label>
            <button type="button" class="password-toggle" onclick="togglePassword()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
            </button>
        </div>
        <div class="input-field">
            <input type="password" name="confirmar_contraseña" id="confirmar_contraseña" required>
            <label>Confirmar contraseña</label>
            <button type="button" class="password-toggle" onclick="togglePasswordConfirm()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
            </button>
        </div>
        <div class="input-field">
            <select name="rol" required>
                <option value="usuario" <?= (isset($_POST['rol']) && $_POST['rol'] === 'usuario') ? 'selected' : '' ?>>Usuario</option>
                <option value="admin" <?= (isset($_POST['rol']) && $_POST['rol'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
            </select>
            <label>Rol</label>
        </div>
        <button type="submit">Registrarse</button>
        <div class="login-link">
            <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
        </div>
    </form>
</div>

<script>
    // JavaScript para mostrar/ocultar contraseñas
    function togglePassword() {
        const passwordInput = document.getElementById('contraseña');
        const toggleButton = document.querySelector('.password-toggle');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            // Actualizar el SVG para ojo abierto
            toggleButton.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
        } else {
            passwordInput.type = 'password';
            // Actualizar el SVG para ojo cerrado
            toggleButton.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.7)"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
        }
    }

    function togglePasswordConfirm() {
        const passwordInput = document.getElementById('confirmar_contraseña');
        const toggleButtons = document.querySelectorAll('.password-toggle');
        // Usamos el segundo botón (índice 1) para confirmar contraseña
        const toggleButton = toggleButtons[1];
        
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