<?php
session_start();
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #023A60 0%, #034d7a 50%, #95D7FF 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #023A60, #95D7FF);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(2, 58, 96, 0.3);
        }
        
        h1 {
            color: #023A60;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #95D7FF;
            box-shadow: 0 0 0 4px rgba(149, 215, 255, 0.2);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #023A60, #034d7a);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(2, 58, 96, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: #023A60;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link a:hover {
            color: #034d7a;
            gap: 8px;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            font-size: 14px;
            animation: slideDown 0.4s ease-out;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            color: #6b7280;
            transition: color 0.3s;
        }
        
        .toggle-icon:hover {
            color: #023A60;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 40px 30px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🎓</div>
            <h1>Panel de Administración</h1>
            <p class="subtitle">Sistema de Gestión de Certificados</p>
        </div>
        
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-error">❌ Credenciales inválidas. Por favor, intente nuevamente.</div>';
        }
        ?>
        
        <form action="admin.php" method="POST">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Ingrese su usuario" autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" required 
                           placeholder="Ingrese su contraseña" autocomplete="current-password">
                    <span class="toggle-icon" onclick="togglePassword()">👁️</span>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                🔐 Iniciar Sesión
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Volver al sitio público</a>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                icon.textContent = '👁️';
            }
        }
        
        // Focus on username field on load
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
        
        // Handle Enter key
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>