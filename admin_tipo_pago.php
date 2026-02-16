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
                    $stmt = $pdo->prepare("INSERT INTO tipo_de_pago (TDP_NOMBRE, TDP_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['nombre']]);
                    $message = 'Tipo de pago creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE tipo_de_pago SET TDP_NOMBRE = ? WHERE TDP_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['id']]);
                    $message = 'Tipo de pago actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE tipo_de_pago SET TDP_ESTADO = 'I' WHERE TDP_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Tipo de pago eliminado exitosamente';
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
$searchQuery = $search ? "AND TDP_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tipo_de_pago WHERE TDP_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT t.*, COUNT(c.COS_ID) as total_costos
    FROM tipo_de_pago t
    LEFT JOIN costo c ON t.TDP_ID = c.TDP_ID AND c.COS_ESTADO = 'A'
    WHERE t.TDP_ESTADO = 'A' $searchQuery
    GROUP BY t.TDP_ID
    ORDER BY t.TDP_ID DESC
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
    <title>Gestión de Tipos de Pago - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Tipos de Pago</h1>
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
                <input type="text" name="search" placeholder="Buscar tipo de pago..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_tipo_pago.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Costos Asociados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tipo['TDP_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($tipo['TDP_NOMBRE']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($tipo['total_costos']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editTipo(<?php echo json_encode($tipo); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteTipo(<?php echo $tipo['TDP_ID']; ?>, <?php echo $tipo['total_costos']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tipos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No se encontraron tipos de pago</td>
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
                <h2 id="modalTitle">Nuevo Tipo de Pago</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="tipoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="tipoId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre del Tipo de Pago *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Efectivo, Transferencia, Tarjeta de Crédito">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Ejemplos: Efectivo, Transferencia Bancaria, Tarjeta de Crédito, Cheque
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
            document.getElementById('modalTitle').textContent = 'Nuevo Tipo de Pago';
        }

        function closeModal() {
            document.getElementById('tipoModal').classList.remove('active');
        }

        function editTipo(tipo) {
            document.getElementById('tipoModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Tipo de Pago';
            document.getElementById('tipoId').value = tipo.TDP_ID;
            document.getElementById('nombre').value = tipo.TDP_NOMBRE;
        }

        function deleteTipo(id, totalCostos) {
            if (totalCostos > 0) {
                if (!confirm(`¡ADVERTENCIA! Este tipo de pago tiene ${totalCostos} costo(s) asociado(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este tipo de pago?')) {
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