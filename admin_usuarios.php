<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin_login.php');
    exit;
}

// Manejo de acciones CRUD
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    // Crear instrucción formal si se proporcionó texto nuevo
                    $inf_id = null;
                    if (!empty($_POST['instruccion']) && $_POST['instruccion'] !== 'nueva') {
                        $inf_id = $_POST['instruccion'];
                    } elseif (!empty($_POST['nueva_instruccion'])) {
                        // Crear nuevo registro de instrucción
                        $stmtInf = $pdo->prepare("
                            INSERT INTO instruccion_formal (INF_TITULO, INF_INSTITUCION, INF_ESTADO, PRR_ID, NV_ID) 
                            VALUES (?, ?, 'A', ?, ?)
                        ");
                        $stmtInf->execute([
                            $_POST['nueva_instruccion'],
                            $_POST['institucion'] ?? 'No especificado',
                            $_POST['parroquia'],
                            1 // Nivel por defecto, ajusta según necesites
                        ]);
                        $inf_id = $pdo->lastInsertId();
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO usuario (
                            US_CEDULA, US_NOMBRE, US_APELLIDO, US_FECHA_NACIMIENTO,
                            US_GENERO, US_TELEFONO, US_CELULAR, US_CORREO,
                            US_ESTADO_CIVIL, US_EDAD, US_TIPO_SANGRE, US_ETNIA,
                            US_ESTADO, PRR_ID, TUS_ID, INF_ID
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'A', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['cedula'],
                        $_POST['nombre'],
                        $_POST['apellido'],
                        $_POST['fecha_nacimiento'],
                        $_POST['genero'],
                        $_POST['telefono'],
                        $_POST['celular'],
                        $_POST['correo'],
                        $_POST['estado_civil'],
                        $_POST['edad'],
                        $_POST['tipo_sangre'],
                        $_POST['etnia'],
                        $_POST['parroquia'],
                        $_POST['tipo_usuario'],
                        $inf_id
                    ]);
                    $message = 'Usuario creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    // Actualizar o crear instrucción formal si se proporcionó
                    $inf_id = null;
                    
                    // Si se seleccionó una instrucción existente
                    if (!empty($_POST['instruccion']) && $_POST['instruccion'] !== 'nueva' && $_POST['instruccion'] !== '') {
                        $inf_id = $_POST['instruccion'];
                    } 
                    // Si se escribió una nueva instrucción
                    elseif (!empty($_POST['nueva_instruccion'])) {
                        // Crear nuevo registro de instrucción
                        $stmtInf = $pdo->prepare("
                            INSERT INTO instruccion_formal (INF_TITULO, INF_INSTITUCION, INF_ESTADO, PRR_ID, NV_ID) 
                            VALUES (?, ?, 'A', ?, ?)
                        ");
                        $stmtInf->execute([
                            $_POST['nueva_instruccion'],
                            $_POST['institucion'] ?? 'No especificado',
                            $_POST['parroquia'],
                            1 // Nivel por defecto
                        ]);
                        $inf_id = $pdo->lastInsertId();
                    }
                    // Si no hay instrucción, obtener la actual del usuario
                    else {
                        $stmtCheck = $pdo->prepare("SELECT INF_ID FROM usuario WHERE US_ID = ?");
                        $stmtCheck->execute([$_POST['id']]);
                        $currentUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                        $inf_id = $currentUser['INF_ID'];
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE usuario SET
                            US_NOMBRE = ?, US_APELLIDO = ?, US_FECHA_NACIMIENTO = ?,
                            US_GENERO = ?, US_TELEFONO = ?, US_CELULAR = ?,
                            US_CORREO = ?, US_ESTADO_CIVIL = ?, US_EDAD = ?,
                            US_TIPO_SANGRE = ?, US_ETNIA = ?,
                            PRR_ID = ?, TUS_ID = ?, INF_ID = ?
                        WHERE US_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['apellido'],
                        $_POST['fecha_nacimiento'],
                        $_POST['genero'],
                        $_POST['telefono'],
                        $_POST['celular'],
                        $_POST['correo'],
                        $_POST['estado_civil'],
                        $_POST['edad'],
                        $_POST['tipo_sangre'],
                        $_POST['etnia'],
                        $_POST['parroquia'],
                        $_POST['tipo_usuario'],
                        $inf_id,
                        $_POST['id']
                    ]);
                    $message = 'Usuario actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE usuario SET US_ESTADO = 'I' WHERE US_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Usuario eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener listas para selects
$parroquias = $pdo->query("SELECT PRR_ID, PRR_NOMBRE FROM parroquia WHERE PRR_ESTADO = 'A' ORDER BY PRR_NOMBRE")->fetchAll();
$tiposUsuario = $pdo->query("SELECT TUS_ID, TUS_DESCRIPCION FROM tipo_de_usuario WHERE TUS_ESTADO = 'A' ORDER BY TUS_DESCRIPCION")->fetchAll();
$niveles = $pdo->query("SELECT NV_ID, NV_NOMBRE FROM niveles WHERE NV_ESTADO = 'A' ORDER BY NV_NOMBRE")->fetchAll();
$instrucciones = $pdo->query("SELECT INF_ID, INF_TITULO, INF_INSTITUCION FROM instruccion_formal WHERE INF_ESTADO = 'A' ORDER BY INF_TITULO")->fetchAll();

// Obtener usuarios con paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND (US_CEDULA LIKE ? OR US_NOMBRE LIKE ? OR US_APELLIDO LIKE ?)" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE US_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT u.*, p.PRR_NOMBRE, t.TUS_DESCRIPCION, i.INF_TITULO
    FROM usuario u
    LEFT JOIN parroquia p ON u.PRR_ID = p.PRR_ID
    LEFT JOIN tipo_de_usuario t ON u.TUS_ID = t.TUS_ID
    LEFT JOIN instruccion_formal i ON u.INF_ID = i.INF_ID
    WHERE u.US_ESTADO = 'A' $searchQuery
    ORDER BY u.US_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Usuarios</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openUserModal()">➕ Nuevo Usuario</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por cédula, nombre o apellido..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_usuarios.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Edad</th>
                        <th>Género</th>
                        <th>Celular</th>
                        <th>Correo</th>
                        <th>Tipo Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['US_CEDULA']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['US_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['US_APELLIDO']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['US_EDAD'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($usuario['US_GENERO'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($usuario['US_CELULAR']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['US_CORREO']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['TUS_DESCRIPCION']); ?></td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editUser(<?php echo json_encode($usuario); ?>)' title="Editar">
                                ✏️
                            </button>
                            <button class="btn-icon btn-delete" onclick="deleteUser(<?php echo $usuario['US_ID']; ?>)" title="Eliminar">
                                🗑️
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No se encontraron usuarios</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">← Anterior</a>
            <?php endif; ?>
            
            <span class="page-info">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">Siguiente →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Usuario</h2>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <form method="POST" id="userForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Cédula *</label>
                        <input type="text" name="cedula" id="cedula" required pattern="\d{10}" 
                               title="Debe tener 10 dígitos" maxlength="10">
                    </div>
                    
                    <div class="form-group">
                        <label>Nombres *</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Apellidos *</label>
                        <input type="text" name="apellido" id="apellido" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Nacimiento *</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Edad</label>
                        <input type="number" name="edad" id="edad" min="0" max="120">
                    </div>
                    
                    <div class="form-group">
                        <label>Género</label>
                        <select name="genero" id="genero">
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Estado Civil</label>
                        <select name="estado_civil" id="estado_civil">
                            <option value="">Seleccione...</option>
                            <option value="Soltero">Soltero/a</option>
                            <option value="Casado">Casado/a</option>
                            <option value="Divorciado">Divorciado/a</option>
                            <option value="Viudo">Viudo/a</option>
                            <option value="Union Libre">Unión Libre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Sangre</label>
                        <select name="tipo_sangre" id="tipo_sangre">
                            <option value="">Seleccione...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Etnia</label>
                        <select name="etnia" id="etnia">
                            <option value="">Seleccione...</option>
                            <option value="Mestizo">Mestizo</option>
                            <option value="Indígena">Indígena</option>
                            <option value="Afroecuatoriano">Afroecuatoriano</option>
                            <option value="Blanco">Blanco</option>
                            <option value="Montubio">Montubio</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="telefono" pattern="\d{7,10}" maxlength="10">
                    </div>
                    
                    <div class="form-group">
                        <label>Celular *</label>
                        <input type="text" name="celular" id="celular" required pattern="\d{10}" maxlength="10">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Correo Electrónico *</label>
                        <input type="email" name="correo" id="correo" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Parroquia *</label>
                        <div class="input-with-button">
                            <select name="parroquia" id="parroquia" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($parroquias as $p): ?>
                                <option value="<?php echo $p['PRR_ID']; ?>"><?php echo htmlspecialchars($p['PRR_NOMBRE']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-add" onclick="openModal('parroquia', 'parroquia')">➕</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Usuario *</label>
                        <select name="tipo_usuario" id="tipo_usuario" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($tiposUsuario as $t): ?>
                            <option value="<?php echo $t['TUS_ID']; ?>"><?php echo htmlspecialchars($t['TUS_DESCRIPCION']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Instrucción Formal</label>
                        <div class="input-with-button">
                            <select name="instruccion" id="instruccion">
                                <option value="">Seleccione...</option>
                                <?php if (!empty($instrucciones)): ?>
                                    <?php foreach ($instrucciones as $i): ?>
                                    <option value="<?php echo $i['INF_ID']; ?>">
                                        <?php echo htmlspecialchars($i['INF_TITULO'] . ' - ' . $i['INF_INSTITUCION']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="btn-add" onclick="openModal('instruccion_formal', 'instruccion')">➕</button>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/admin-usuarios.js"></script>
    <script src="js/modal-manager.js"></script>

    <div id="modal-container"></div>
</body>
</html>