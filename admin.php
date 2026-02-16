<?php
session_start();
require_once 'db.php';

// Manejar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin123') {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = $_POST['username'];
        header('Location: admin.php');
        exit;
    } else {
        header('Location: admin_login.php?error=1');
        exit;
    }
}

if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin_login.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

function getStats($pdo) {
    $stats = [];
    $stats['usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuario WHERE US_ESTADO = 'A'")->fetchColumn();
    $stats['matriculas'] = $pdo->query("SELECT COUNT(*) FROM matricula WHERE MAT_ESTADO = 'A'")->fetchColumn();
    $stats['certificados_curso'] = $pdo->query("SELECT COUNT(*) FROM presentacion_certificado_curso WHERE PCC_ESTADO = 'A'")->fetchColumn();
    $stats['certificados_oec'] = $pdo->query("SELECT COUNT(*) FROM presentacion_certificado_oec WHERE POEC_ESTADO = 'A'")->fetchColumn();
    $stats['empresas'] = $pdo->query("SELECT COUNT(*) FROM empresa WHERE EMP_ESTADO = 'A'")->fetchColumn();
    $stats['cursos'] = $pdo->query("SELECT COUNT(*) FROM curso WHERE CUR_ESTADO = 'A'")->fetchColumn();
    return $stats;
}

$stats = getStats($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Certificados</title>
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
        
        :root {
            --primary-dark: #023A60;
            --primary-medium: #034d7a;
            --primary-light: #95D7FF;
            --accent: #6bc5ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
            color: white;
            padding: 30px 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 0 25px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 14px;
            opacity: 0.7;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .menu-section {
            padding: 15px 25px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 10px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 14px;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--primary-light);
            padding-left: 21px;
        }
        
        .sidebar-menu .icon {
            width: 24px;
            margin-right: 12px;
            font-size: 20px;
        }
        
        .logout-section {
            position: fixed;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            padding: 20px 25px;
            background: rgba(0, 0, 0, 0.2);
            z-index: 1001;
        }
        
        .logout-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0 140px 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            color: var(--primary-dark);
            font-size: 28px;
            font-weight: 700;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent) 100%);
            border-radius: 25px;
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.primary::before { background: var(--primary-dark); }
        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.info::before { background: var(--primary-light); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-card.primary .stat-icon { background: rgba(2, 58, 96, 0.1); color: var(--primary-dark); }
        .stat-card.success .stat-icon { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-card.warning .stat-icon { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-card.info .stat-icon { background: rgba(149, 215, 255, 0.2); color: var(--primary-medium); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🎓 CAPTEEC</h2>
            <p>Panel de Administración</p>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="admin.php" class="active">
                <span class="icon">📊</span> Dashboard
            </a></li>
            
            <div class="menu-section">Gestión Principal</div>
            
            <li><a href="admin_usuarios.php">
                <span class="icon">👥</span> Usuarios
            </a></li>
            <li><a href="admin_empresas.php">
                <span class="icon">🏢</span> Empresas
            </a></li>
            <li><a href="admin_cursos.php">
                <span class="icon">📚</span> Cursos
            </a></li>
            <li><a href="admin_matriculas.php">
                <span class="icon">📝</span> Matrículas
            </a></li>
            
            <div class="menu-section">Certificaciones</div>
            
            <li><a href="admin_certificados.php">
                <span class="icon">🎓</span> Certificados Curso
            </a></li>
            <li><a href="admin_oec.php">
                <span class="icon">✅</span> Certificados OEC
            </a></li>
            
            <div class="menu-section">Configuración</div>
            
            <li><a href="admin_categorias.php">
                <span class="icon">📂</span> Categorías
            </a></li>
            <li><a href="admin_modalidad.php">
                <span class="icon">💻</span> Modalidades
            </a></li>
            <li><a href="admin_horarios.php">
                <span class="icon">🕐</span> Horarios
            </a></li>
            <li><a href="admin_niveles.php">
                <span class="icon">🎯</span> Niveles
            </a></li>
            <li><a href="admin_tipo_usuario.php">
                <span class="icon">👤</span> Tipos de Usuario
            </a></li>
            <li><a href="admin_costos.php">
                <span class="icon">💰</span> Costos
            </a></li>
            <li><a href="admin_tipo_pago.php">
                <span class="icon">💳</span> Tipos de Pago
            </a></li>
            <li><a href="admin_requisitos.php">
                <span class="icon">📋</span> Requisitos
            </a></li>
            <li><a href="admin_certificaciones_oec.php">
                <span class="icon">🏅</span> Certificaciones OEC
            </a></li>
            <li><a href="admin_lugares.php">
                <span class="icon">📍</span> Lugares de Examinación
            </a></li>
            
            <div class="menu-section">Geografía</div>
            
            <li><a href="admin_pais.php">
                <span class="icon">🌎</span> Países
            </a></li>
            <li><a href="admin_provincia.php">
                <span class="icon">🗺️</span> Provincias
            </a></li>
            <li><a href="admin_canton.php">
                <span class="icon">🏘️</span> Cantones
            </a></li>
            <li><a href="admin_parroquia.php">
                <span class="icon">📌</span> Parroquias
            </a></li>
        </ul>
        
        <div class="logout-section">
            <a href="?action=logout" class="logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </aside>
    
    <main class="main-content">
        <div class="top-bar">
            <h1>Panel de Administración</h1>
            <div class="admin-info">
                <span>👤</span>
                <span><?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'Administrador'); ?></span>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($stats['usuarios']); ?></div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($stats['matriculas']); ?></div>
                        <div class="stat-label">Matrículas</div>
                    </div>
                    <div class="stat-icon">📝</div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($stats['certificados_curso']); ?></div>
                        <div class="stat-label">Certificados Curso</div>
                    </div>
                    <div class="stat-icon">🎓</div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($stats['certificados_oec']); ?></div>
                        <div class="stat-label">Certificados OEC</div>
                    </div>
                    <div class="stat-icon">✅</div>
                </div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($stats['empresas']); ?></div>
                        <div class="stat-label">Empresas</div>
                    </div>
                    <div class="stat-icon">🏢</div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($stats['cursos']); ?></div>
                        <div class="stat-label">Cursos Disponibles</div>
                    </div>
                    <div class="stat-icon">📚</div>
                </div>
            </div>
        </div>
        
        <div style="background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);">
            <h2 style="color: #023A60; margin-bottom: 20px; font-size: 24px;">Accesos Rápidos</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="admin_usuarios.php" style="padding: 20px; background: linear-gradient(135deg, #023A60, #034d7a); color: white; text-decoration: none; border-radius: 12px; text-align: center; font-weight: 600; transition: all 0.3s;">
                    👥 Gestionar Usuarios
                </a>
                <a href="admin_cursos.php" style="padding: 20px; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 12px; text-align: center; font-weight: 600; transition: all 0.3s;">
                    📚 Gestionar Cursos
                </a>
                <a href="admin_matriculas.php" style="padding: 20px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; text-decoration: none; border-radius: 12px; text-align: center; font-weight: 600; transition: all 0.3s;">
                    📝 Nueva Matrícula
                </a>
                <a href="admin_certificados.php" style="padding: 20px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; text-decoration: none; border-radius: 12px; text-align: center; font-weight: 600; transition: all 0.3s;">
                    🎓 Emitir Certificado
                </a>
            </div>
        </div>
    </main>
</body>
</html>