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
                    $stmt = $pdo->prepare("INSERT INTO pais (PS_NOMBRE, PS_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['nombre']]);
                    $message = 'País creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE pais SET PS_NOMBRE = ? WHERE PS_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['id']]);
                    $message = 'País actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE pais SET PS_ESTADO = 'I' WHERE PS_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'País eliminado exitosamente';
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
$searchQuery = $search ? "AND PS_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pais WHERE PS_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT p.*, COUNT(pr.PR_ID) as total_provincias
    FROM pais p
    LEFT JOIN provincia pr ON p.PS_ID = pr.PS_ID AND pr.PR_ESTADO = 'A'
    WHERE p.PS_ESTADO = 'A' $searchQuery
    GROUP BY p.PS_ID
    ORDER BY p.PS_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$paises = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Países - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Países</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo País</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar país..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_pais.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del País</th>
                        <th>Provincias</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paises as $pais): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pais['PS_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($pais['PS_NOMBRE']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($pais['total_provincias']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editPais(<?php echo json_encode($pais); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deletePais(<?php echo $pais['PS_ID']; ?>, <?php echo $pais['total_provincias']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paises)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No se encontraron países</td>
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

    <div id="paisModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo País</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="paisForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="paisId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre del País *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Ecuador, Colombia, Perú">
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
            document.getElementById('paisModal').classList.add('active');
            document.getElementById('paisForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo País';
        }

        function closeModal() {
            document.getElementById('paisModal').classList.remove('active');
        }

        function editPais(pais) {
            document.getElementById('paisModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar País';
            document.getElementById('paisId').value = pais.PS_ID;
            document.getElementById('nombre').value = pais.PS_NOMBRE;
        }

        function deletePais(id, totalProvincias) {
            if (totalProvincias > 0) {
                if (!confirm(`¡ADVERTENCIA! Este país tiene ${totalProvincias} provincia(s) asociada(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este país?')) {
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
            const modal = document.getElementById('paisModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>