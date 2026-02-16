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
                    $stmt = $pdo->prepare("
                        INSERT INTO contrato (CTT_CODIGO, CTT_ESTADO) 
                        VALUES (?, 'A')
                    ");
                    $stmt->execute([
                        $_POST['codigo']
                    ]);
                    $message = 'Contrato creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE contrato SET
                            CTT_CODIGO = ?
                        WHERE CTT_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['codigo'],
                        $_POST['id']
                    ]);
                    $message = 'Contrato actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE contrato SET CTT_ESTADO = 'I' WHERE CTT_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Contrato eliminado exitosamente';
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
$searchQuery = $search ? "AND CTT_CODIGO LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE CTT_ESTADO = 'A' $searchQuery");
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
           (SELECT COUNT(*) FROM matricula WHERE CTT_ID = c.CTT_ID AND MAT_ESTADO = 'A') as total_matriculas
    FROM contrato c
    WHERE c.CTT_ESTADO = 'A' $searchQuery
    ORDER BY c.CTT_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$contratos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contratos - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Contratos</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Contrato</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por código..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_contratos.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código de Contrato</th>
                        <th>Matrículas Asociadas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contratos as $contrato): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($contrato['CTT_ID']); ?></td>
                        <td><strong style="font-family: monospace; font-size: 16px;"><?php echo htmlspecialchars($contrato['CTT_CODIGO']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($contrato['total_matriculas']); ?> matrícula(s)
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editContrato(<?php echo json_encode($contrato); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteContrato(<?php echo $contrato['CTT_ID']; ?>, <?php echo $contrato['total_matriculas']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contratos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">
                            <div style="padding: 40px; text-align: center;">
                                <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
                                <p style="color: #666; font-size: 16px; margin-bottom: 8px;">No hay contratos registrados</p>
                                <p style="color: #999; font-size: 14px;">Crea el primer contrato haciendo clic en "Nuevo Contrato"</p>
                            </div>
                        </td>
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

    <div id="contratoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Contrato</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="contratoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="contratoId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Código de Contrato *</label>
                        <input type="number" name="codigo" id="codigo" required 
                               placeholder="Ej: 100001" min="1" max="999999999">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Ingrese un número único para identificar este contrato
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
            document.getElementById('contratoModal').classList.add('active');
            document.getElementById('contratoForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Contrato';
            
            // Sugerir código inicial si no hay contratos
            document.getElementById('codigo').value = 100001;
        }

        function closeModal() {
            document.getElementById('contratoModal').classList.remove('active');
        }

        function editContrato(contrato) {
            document.getElementById('contratoModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Contrato';
            document.getElementById('contratoId').value = contrato.CTT_ID;
            document.getElementById('codigo').value = contrato.CTT_CODIGO;
        }

        function deleteContrato(id, totalMatriculas) {
            if (totalMatriculas > 0) {
                if (!confirm(`¡ADVERTENCIA! Este contrato tiene ${totalMatriculas} matrícula(s) asociada(s).\n\n¿Está seguro de que desea eliminarlo? Esto solo lo marcará como inactivo.`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este contrato?')) {
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
            const modal = document.getElementById('contratoModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });

        // Auto-hide alert
        window.addEventListener('load', function() {
            const alert = document.getElementById('alert');
            if (alert) {
                setTimeout(function() {
                    alert.style.animation = 'slideUp 0.4s ease-out reverse';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 400);
                }, 5000);
            }
        });

        // Validar que el código sea un número positivo
        document.getElementById('codigo').addEventListener('input', function() {
            if (this.value < 1) {
                this.value = 1;
            }
        });
    </script>
</body>
</html>