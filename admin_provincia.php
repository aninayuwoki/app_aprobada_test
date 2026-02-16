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
                    $stmt = $pdo->prepare("INSERT INTO provincia (PR_NOMBRE, PR_ESTADO, PS_ID) VALUES (?, 'A', ?)");
                    $stmt->execute([$_POST['nombre'], $_POST['pais']]);
                    $message = 'Provincia creada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE provincia SET PR_NOMBRE = ?, PS_ID = ? WHERE PR_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['pais'], $_POST['id']]);
                    $message = 'Provincia actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE provincia SET PR_ESTADO = 'I' WHERE PR_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Provincia eliminada exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener países para el select
$paises = $pdo->query("SELECT PS_ID, PS_NOMBRE FROM pais WHERE PS_ESTADO = 'A' ORDER BY PS_NOMBRE")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND pr.PR_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM provincia pr WHERE pr.PR_ESTADO = 'A' $searchQuery
");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT pr.*, ps.PS_NOMBRE, COUNT(c.CAN_ID) as total_cantones
    FROM provincia pr
    INNER JOIN pais ps ON pr.PS_ID = ps.PS_ID
    LEFT JOIN canton c ON pr.PR_ID = c.PR_ID AND c.CAN_ESTADO = 'A'
    WHERE pr.PR_ESTADO = 'A' $searchQuery
    GROUP BY pr.PR_ID
    ORDER BY pr.PR_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$provincias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Provincias - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Provincias</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Provincia</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar provincia..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_provincia.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Provincia</th>
                        <th>País</th>
                        <th>Cantones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($provincias as $provincia): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($provincia['PR_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($provincia['PR_NOMBRE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($provincia['PS_NOMBRE']); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($provincia['total_cantones']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editProvincia(<?php echo json_encode($provincia); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteProvincia(<?php echo $provincia['PR_ID']; ?>, <?php echo $provincia['total_cantones']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($provincias)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron provincias</td>
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

    <div id="provinciaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Provincia</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="provinciaForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="provinciaId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>País *</label>
                        <select name="pais" id="pais" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($paises as $p): ?>
                            <option value="<?php echo $p['PS_ID']; ?>"><?php echo htmlspecialchars($p['PS_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre de la Provincia *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Guayas, Pichincha, Azuay">
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
            document.getElementById('provinciaModal').classList.add('active');
            document.getElementById('provinciaForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Provincia';
        }

        function closeModal() {
            document.getElementById('provinciaModal').classList.remove('active');
        }

        function editProvincia(provincia) {
            document.getElementById('provinciaModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Provincia';
            document.getElementById('provinciaId').value = provincia.PR_ID;
            document.getElementById('nombre').value = provincia.PR_NOMBRE;
            document.getElementById('pais').value = provincia.PS_ID;
        }

        function deleteProvincia(id, totalCantones) {
            if (totalCantones > 0) {
                if (!confirm(`¡ADVERTENCIA! Esta provincia tiene ${totalCantones} cantón(es) asociado(s).\n\n¿Está seguro de que desea eliminarla?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar esta provincia?')) {
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
            const modal = document.getElementById('provinciaModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>