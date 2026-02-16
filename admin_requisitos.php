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
                    $stmt = $pdo->prepare("INSERT INTO requisitos_oec_cursos (RQT_NOMBRE, RQT_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['nombre']]);
                    $message = 'Requisito creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE requisitos_oec_cursos SET RQT_NOMBRE = ? WHERE RQT_ID = ?");
                    $stmt->execute([$_POST['nombre'], $_POST['id']]);
                    $message = 'Requisito actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE requisitos_oec_cursos SET RQT_ESTADO = 'I' WHERE RQT_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Requisito eliminado exitosamente';
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
$searchQuery = $search ? "AND RQT_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM requisitos_oec_cursos WHERE RQT_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT r.*, COUNT(c.CUR_ID) as total_cursos
    FROM requisitos_oec_cursos r
    LEFT JOIN curso c ON r.RQT_ID = c.RQT_ID AND c.CUR_ESTADO = 'A'
    WHERE r.RQT_ESTADO = 'A' $searchQuery
    GROUP BY r.RQT_ID
    ORDER BY r.RQT_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$requisitos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Requisitos - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Requisitos OEC/Cursos</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Requisito</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar requisito..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_requisitos.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Requisito</th>
                        <th>Cursos Asociados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisitos as $req): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['RQT_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($req['RQT_NOMBRE']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($req['total_cursos']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editRequisito(<?php echo json_encode($req); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteRequisito(<?php echo $req['RQT_ID']; ?>, <?php echo $req['total_cursos']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requisitos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No se encontraron requisitos</td>
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

    <div id="requisitoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Requisito</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="requisitoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="requisitoId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre del Requisito *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Certificado médico, Cédula de identidad, Foto tamaño carnet">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Especifique claramente el requisito necesario para el curso u OEC
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
            document.getElementById('requisitoModal').classList.add('active');
            document.getElementById('requisitoForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Requisito';
        }

        function closeModal() {
            document.getElementById('requisitoModal').classList.remove('active');
        }

        function editRequisito(req) {
            document.getElementById('requisitoModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Requisito';
            document.getElementById('requisitoId').value = req.RQT_ID;
            document.getElementById('nombre').value = req.RQT_NOMBRE;
        }

        function deleteRequisito(id, totalCursos) {
            if (totalCursos > 0) {
                if (!confirm(`¡ADVERTENCIA! Este requisito tiene ${totalCursos} curso(s) asociado(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este requisito?')) {
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
            const modal = document.getElementById('requisitoModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>