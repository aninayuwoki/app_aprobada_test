<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Consulta pública de certificaciones - Sistema de verificación de certificados">
    <title>Consulta Pública de Certificaciones</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="shortcut icon" type="image/x-icon" href="capatec.ico">
    
    <!-- Fuente Inter de Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Estilos inline para animación de carga inicial -->
    <style>
        body.loading {
            overflow: hidden;
        }
        
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #023A60 0%, #034d7a 50%, #95D7FF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        
        .page-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader-content {
            text-align: center;
        }
        
        .loader-spinner {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(149, 215, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 30px;
        }
        
        .loader-text {
            color: white;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 2px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="loading">
    <!-- Loader de página -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <div class="loader-text">CARGANDO...</div>
        </div>
    </div>
    
    <!-- Navegación mejorada -->
    <nav>
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <a href="index.php">📋 Consulta de Matrículas</a>
            <a href="certificados.php">🎓 Consulta de Certificados</a>
        </div>
    </nav>
    
    <script>
        // Remover el loader cuando la página cargue
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loader = document.getElementById('pageLoader');
                loader.classList.add('hidden');
                document.body.classList.remove('loading');
                
                // Remover el loader del DOM después de la animación
                setTimeout(function() {
                    loader.remove();
                }, 500);
            }, 500);
        });
    </script>