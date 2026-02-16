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
                    $stmt = $pdo->prepare("INSERT INTO costo (COS_PAGO_TOTAL, COS_ESTADO, TDP_ID) VALUES (?, 'A', ?)");
                    $stmt->execute([$_POST['pago_total'], $_POST['tipo_pago']]);
                    $message = 'Costo creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE costo SET COS_PAGO_TOTAL = ?, TDP_ID = ? WHERE COS_ID = ?");
                    $stmt->execute([$_POST['pago_total'], $_POST['tipo_pago'], $_POST['id']]);
                    $message = 'Costo actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE costo SET COS_ESTADO = 'I' WHERE COS_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Costo eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener tipos de pago
$tipos_pago = $pdo->query("SELECT TDP_ID, TDP_NOMBRE FROM tipo_de_pago WHERE TDP_ESTADO = 'A' ORDER BY TDP_NOMBRE")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND COS_PAGO_TOTAL LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM costo WHERE COS_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT c.*, t.TDP_NOMBRE,
           COUNT(DISTINCT cur.CUR_ID) as total_cursos,
           COUNT(DISTINCT r.RGO_ID) as total_registros
    FROM costo c
    LEFT JOIN tipo_de_pago t ON c.TDP_ID = t.TDP_ID
    LEFT JOIN curso cur ON c.COS_ID = cur.COS_ID AND cur.CUR_ESTADO = 'A'
    LEFT JOIN registro_oec r ON c.COS_ID = r.COS_ID AND r.RGO_ESTADO = 'A'
    WHERE c.COS_ESTADO = 'A' $searchQuery
    GROUP BY c.COS_ID
    ORDER BY c.COS_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$costos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Costos - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Costos</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Costo</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por monto..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_costos.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Monto Total</th>
                        <th>Tipo de Pago</th>
                        <th>Cursos</th>
                        <th>Registros OEC</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costos as $costo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($costo['COS_ID']); ?></td>
                        <td><strong style="color: #10b981; font-size: 16px;">$<?php echo number_format($costo['COS_PAGO_TOTAL'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($costo['TDP_NOMBRE'] ?? 'N/A'); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($costo['total_cursos']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($costo['total_registros']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editCosto(<?php echo json_encode($costo); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteCosto(<?php echo $costo['COS_ID']; ?>, <?php echo $costo['total_cursos'] + $costo['total_registros']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($costos)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron costos</td>
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

    <div id="costoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Costo</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="costoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="costoId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Monto Total *</label>
                        <input type="number" name="pago_total" id="pago_total" required 
                               min="0" max="9999.99" step="0.01" 
                               placeholder="Ej: 150.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Pago *</label>
                        <select name="tipo_pago" id="tipo_pago" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($tipos_pago as $tp): ?>
                            <option value="<?php echo $tp['TDP_ID']; ?>"><?php echo htmlspecialchars($tp['TDP_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
            document.getElementById('costoModal').classList.add('active');
            document.getElementById('costoForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Costo';
        }

        function closeModal() {
            document.getElementById('costoModal').classList.remove('active');
        }

        function editCosto(costo) {
            document.getElementById('costoModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Costo';
            document.getElementById('costoId').value = costo.COS_ID;
            document.getElementById('pago_total').value = costo.COS_PAGO_TOTAL;
            document.getElementById('tipo_pago').value = costo.TDP_ID;
        }

        function deleteCosto(id, totalAsociados) {
            if (totalAsociados > 0) {
                if (!confirm(`¡ADVERTENCIA! Este costo tiene ${totalAsociados} curso(s) o registro(s) asociado(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este costo?')) {
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
            const modal = document.getElementById('costoModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>