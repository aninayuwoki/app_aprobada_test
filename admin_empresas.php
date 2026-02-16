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
                        INSERT INTO empresa (EMP_NOMBRE, EMP_RUC, EMP_DIRECCION, EMP_TELEFONO, EMP_ESTADO) 
                        VALUES (?, ?, ?, ?, 'A')
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['ruc'],
                        $_POST['direccion'],
                        $_POST['telefono']
                    ]);
                    $message = 'Empresa creada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE empresa SET
                            EMP_NOMBRE = ?, EMP_RUC = ?, EMP_DIRECCION = ?, EMP_TELEFONO = ?
                        WHERE EMP_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['ruc'],
                        $_POST['direccion'],
                        $_POST['telefono'],
                        $_POST['id']
                    ]);
                    $message = 'Empresa actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE empresa SET EMP_ESTADO = 'I' WHERE EMP_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Empresa eliminada exitosamente';
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
$searchQuery = $search ? "AND (EMP_NOMBRE LIKE ? OR EMP_RUC LIKE ?)" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM empresa WHERE EMP_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam, $searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT * FROM empresa 
    WHERE EMP_ESTADO = 'A' $searchQuery
    ORDER BY EMP_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$empresas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Empresas</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Empresa</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por nombre o RUC..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_empresas.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>RUC</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empresas as $empresa): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($empresa['EMP_ID']); ?></td>
                        <td><?php echo htmlspecialchars($empresa['EMP_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($empresa['EMP_RUC']); ?></td>
                        <td><?php echo htmlspecialchars($empresa['EMP_DIRECCION']); ?></td>
                        <td><?php echo htmlspecialchars($empresa['EMP_TELEFONO']); ?></td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editEmpresa(<?php echo json_encode($empresa); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteEmpresa(<?php echo $empresa['EMP_ID']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($empresas)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron empresas</td>
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

    <div id="empresaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Empresa</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="empresaForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="empresaId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre de la Empresa *</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label>RUC *</label>
                        <input type="text" name="ruc" id="ruc" required pattern="\d{13}" 
                               title="Debe tener 13 dígitos" maxlength="13">
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="text" name="telefono" id="telefono" required maxlength="50">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Dirección *</label>
                        <textarea name="direccion" id="direccion" required rows="3"></textarea>
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
            document.getElementById('empresaModal').classList.add('active');
            document.getElementById('empresaForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Empresa';
        }

        function closeModal() {
            document.getElementById('empresaModal').classList.remove('active');
        }

        function editEmpresa(empresa) {
            document.getElementById('empresaModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Empresa';
            document.getElementById('empresaId').value = empresa.EMP_ID;
            document.getElementById('nombre').value = empresa.EMP_NOMBRE;
            document.getElementById('ruc').value = empresa.EMP_RUC;
            document.getElementById('direccion').value = empresa.EMP_DIRECCION;
            document.getElementById('telefono').value = empresa.EMP_TELEFONO;
        }

        function deleteEmpresa(id) {
            if (confirm('¿Está seguro de que desea eliminar esta empresa?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('empresaModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });

        document.getElementById('ruc').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 13);
        });
    </script>
</body>
</html>