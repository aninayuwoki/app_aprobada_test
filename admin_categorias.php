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
                    $stmt = $pdo->prepare("INSERT INTO categoria_curso_y_oec (CCO_NOMBRE, CCO_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['nombre']]);
                    $message = 'Categoría creada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE categoria_curso_y_oec SET CCO_NOMBRE = ? WHERE CCO_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['id']]);
                    $message = 'Categoría actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE categoria_curso_y_oec SET CCO_ESTADO = 'I' WHERE CCO_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Categoría eliminada exitosamente';
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
$searchQuery = $search ? "AND CCO_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM categoria_curso_y_oec WHERE CCO_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(DISTINCT cur.CUR_ID) as total_cursos,
           COUNT(DISTINCT cert.CON_ID) as total_certificaciones
    FROM categoria_curso_y_oec c
    LEFT JOIN curso cur ON c.CCO_ID = cur.CCO_ID AND cur.CUR_ESTADO = 'A'
    LEFT JOIN certificaciones_oec cert ON c.CCO_ID = cert.CCO_ID AND cert.CON_ESTADO = 'A'
    WHERE c.CCO_ESTADO = 'A' $searchQuery
    GROUP BY c.CCO_ID
    ORDER BY c.CCO_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$categorias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Categorías de Cursos y OEC</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Categoría</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar categoría..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_categorias.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Categoría</th>
                        <th>Cursos</th>
                        <th>Certificaciones OEC</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['CCO_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($cat['CCO_NOMBRE']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($cat['total_cursos']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($cat['total_certificaciones']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editCategoria(<?php echo json_encode($cat); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteCategoria(<?php echo $cat['CCO_ID']; ?>, <?php echo $cat['total_cursos'] + $cat['total_certificaciones']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron categorías</td>
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

    <div id="categoriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Categoría</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="categoriaForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="categoriaId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre de la Categoría *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Seguridad Industrial, Primeros Auxilios, Operador de Maquinaria">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Esta categoría se usará para agrupar cursos y certificaciones OEC
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
            document.getElementById('categoriaModal').classList.add('active');
            document.getElementById('categoriaForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Categoría';
        }

        function closeModal() {
            document.getElementById('categoriaModal').classList.remove('active');
        }

        function editCategoria(categoria) {
            document.getElementById('categoriaModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Categoría';
            document.getElementById('categoriaId').value = categoria.CCO_ID;
            document.getElementById('nombre').value = categoria.CCO_NOMBRE;
        }

        function deleteCategoria(id, totalAsociados) {
            if (totalAsociados > 0) {
                if (!confirm(`¡ADVERTENCIA! Esta categoría tiene ${totalAsociados} curso(s) o certificación(es) asociada(s).\n\n¿Está seguro de que desea eliminarla?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar esta categoría?')) {
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
            const modal = document.getElementById('categoriaModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>