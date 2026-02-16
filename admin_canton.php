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
                    $stmt = $pdo->prepare("INSERT INTO canton (CAN_NOMBRE, CAN_ESTADO, PR_ID) VALUES (?, 'A', ?)");
                    $stmt->execute([$_POST['nombre'], $_POST['provincia']]);
                    $message = 'Cantón creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE canton SET CAN_NOMBRE = ?, PR_ID = ? WHERE CAN_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['provincia'], $_POST['id']]);
                    $message = 'Cantón actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE canton SET CAN_ESTADO = 'I' WHERE CAN_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Cantón eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener provincias para el select
$provincias = $pdo->query("
    SELECT pr.PR_ID, pr.PR_NOMBRE, ps.PS_NOMBRE as PAIS_NOMBRE 
    FROM provincia pr 
    INNER JOIN pais ps ON pr.PS_ID = ps.PS_ID
    WHERE pr.PR_ESTADO = 'A' 
    ORDER BY pr.PR_NOMBRE
")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND c.CAN_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM canton c WHERE c.CAN_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT c.*, pr.PR_NOMBRE, ps.PS_NOMBRE, COUNT(prr.PRR_ID) as total_parroquias
    FROM canton c
    INNER JOIN provincia pr ON c.PR_ID = pr.PR_ID
    INNER JOIN pais ps ON pr.PS_ID = ps.PS_ID
    LEFT JOIN parroquia prr ON c.CAN_ID = prr.CAN_ID AND prr.PRR_ESTADO = 'A'
    WHERE c.CAN_ESTADO = 'A' $searchQuery
    GROUP BY c.CAN_ID
    ORDER BY c.CAN_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$cantones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cantones - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Cantones</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Cantón</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar cantón..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_canton.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Cantón</th>
                        <th>Provincia</th>
                        <th>País</th>
                        <th>Parroquias</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cantones as $canton): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($canton['CAN_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($canton['CAN_NOMBRE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($canton['PR_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($canton['PS_NOMBRE']); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($canton['total_parroquias']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editCanton(<?php echo json_encode($canton); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteCanton(<?php echo $canton['CAN_ID']; ?>, <?php echo $canton['total_parroquias']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cantones)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron cantones</td>
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

    <div id="cantonModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Cantón</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="cantonForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="cantonId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Provincia *</label>
                        <select name="provincia" id="provincia" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($provincias as $pr): ?>
                            <option value="<?php echo $pr['PR_ID']; ?>">
                                <?php echo htmlspecialchars($pr['PR_NOMBRE'] . ' - ' . $pr['PAIS_NOMBRE']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre del Cantón *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Guayaquil, Quito, Cuenca">
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
            document.getElementById('cantonModal').classList.add('active');
            document.getElementById('cantonForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Cantón';
        }

        function closeModal() {
            document.getElementById('cantonModal').classList.remove('active');
        }

        function editCanton(canton) {
            document.getElementById('cantonModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Cantón';
            document.getElementById('cantonId').value = canton.CAN_ID;
            document.getElementById('nombre').value = canton.CAN_NOMBRE;
            document.getElementById('provincia').value = canton.PR_ID;
        }

        function deleteCanton(id, totalParroquias) {
            if (totalParroquias > 0) {
                if (!confirm(`¡ADVERTENCIA! Este cantón tiene ${totalParroquias} parroquia(s) asociada(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este cantón?')) {
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
            const modal = document.getElementById('cantonModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>