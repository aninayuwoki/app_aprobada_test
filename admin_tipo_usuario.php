<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("INSERT INTO tipo_de_usuario (TUS_DESCRIPCION, TUS_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['descripcion']]);
                    $message = 'Tipo de usuario creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE tipo_de_usuario SET TUS_DESCRIPCION = ? WHERE TUS_ID = ?");
                    $stmt->execute([$_POST['descripcion'], $_POST['id']]);
                    $message = 'Tipo de usuario actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE tipo_de_usuario SET TUS_ESTADO = 'I' WHERE TUS_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Tipo de usuario eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND TUS_DESCRIPCION LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tipo_de_usuario WHERE TUS_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT t.*, COUNT(u.US_ID) as total_usuarios
    FROM tipo_de_usuario t
    LEFT JOIN usuario u ON t.TUS_ID = u.TUS_ID AND u.US_ESTADO = 'A'
    WHERE t.TUS_ESTADO = 'A' $searchQuery
    GROUP BY t.TUS_ID
    ORDER BY t.TUS_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$tipos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Usuario - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Tipos de Usuario</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Tipo</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar tipo de usuario..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_tipo_usuario.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Usuarios Asignados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tipo['TUS_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($tipo['TUS_DESCRIPCION']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($tipo['total_usuarios']); ?> usuario(s)
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editTipo(<?php echo json_encode($tipo); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteTipo(<?php echo $tipo['TUS_ID']; ?>, <?php echo $tipo['total_usuarios']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tipos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No se encontraron tipos de usuario</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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

    <div id="tipoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Tipo de Usuario</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="tipoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="tipoId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Descripción del Tipo *</label>
                        <textarea name="descripcion" id="descripcion" required rows="3" 
                                  placeholder="Ej: Estudiante, Instructor, Administrador, Supervisor"></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Describe claramente el rol o tipo de usuario en el sistema
                        </small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('tipoModal').classList.add('active');
            document.getElementById('tipoForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Tipo de Usuario';
        }

        function closeModal() {
            document.getElementById('tipoModal').classList.remove('active');
        }

        function editTipo(tipo) {
            document.getElementById('tipoModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Tipo de Usuario';
            document.getElementById('tipoId').value = tipo.TUS_ID;
            document.getElementById('descripcion').value = tipo.TUS_DESCRIPCION;
        }

        function deleteTipo(id, totalUsuarios) {
            if (totalUsuarios > 0) {
                if (!confirm(`¡ADVERTENCIA! Este tipo tiene ${totalUsuarios} usuario(s) asignado(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este tipo de usuario?')) {
                    return;
                }
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('tipoModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>