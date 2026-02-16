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
                    $stmt = $pdo->prepare("INSERT INTO modalidad (MDL_NOMBRE, MDL_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['nombre']]);
                    $message = 'Modalidad creada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE modalidad SET MDL_NOMBRE = ? WHERE MDL_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['id']]);
                    $message = 'Modalidad actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE modalidad SET MDL_ESTADO = 'I' WHERE MDL_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Modalidad eliminada exitosamente';
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
$searchQuery = $search ? "AND MDL_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM modalidad WHERE MDL_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT m.*, COUNT(c.CUR_ID) as total_cursos
    FROM modalidad m
    LEFT JOIN curso c ON m.MDL_ID = c.MDL_ID AND c.CUR_ESTADO = 'A'
    WHERE m.MDL_ESTADO = 'A' $searchQuery
    GROUP BY m.MDL_ID
    ORDER BY m.MDL_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$modalidades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Modalidades - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Modalidades</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Modalidad</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar modalidad..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_modalidad.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Modalidad</th>
                        <th>Cursos Asociados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modalidades as $modalidad): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($modalidad['MDL_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($modalidad['MDL_NOMBRE']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($modalidad['total_cursos']); ?> curso(s)
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editModalidad(<?php echo json_encode($modalidad); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteModalidad(<?php echo $modalidad['MDL_ID']; ?>, <?php echo $modalidad['total_cursos']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($modalidades)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No se encontraron modalidades</td>
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

    <div id="modalidadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Modalidad</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="modalidadForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="modalidadId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre de la Modalidad *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Presencial, Virtual, Híbrida, Semipresencial">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Ejemplos: Presencial, Virtual/Online, Híbrida, Semipresencial, A Distancia
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
            document.getElementById('modalidadModal').classList.add('active');
            document.getElementById('modalidadForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Modalidad';
        }

        function closeModal() {
            document.getElementById('modalidadModal').classList.remove('active');
        }

        function editModalidad(modalidad) {
            document.getElementById('modalidadModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Modalidad';
            document.getElementById('modalidadId').value = modalidad.MDL_ID;
            document.getElementById('nombre').value = modalidad.MDL_NOMBRE;
        }

        function deleteModalidad(id, totalCursos) {
            if (totalCursos > 0) {
                if (!confirm(`¡ADVERTENCIA! Esta modalidad tiene ${totalCursos} curso(s) asociado(s).\n\n¿Está seguro de que desea eliminarla?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar esta modalidad?')) {
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
            const modal = document.getElementById('modalidadModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>