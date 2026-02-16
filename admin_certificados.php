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
                    // Generar código único
                    $codigo = 'CERT-' . date('Y') . '-' . rand(1000, 9999);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO presentacion_certificado_curso (PCC_CODIGO_UNICO, PCC_ESTADO, MAT_ID) 
                        VALUES (?, 'A', ?)
                    ");
                    $stmt->execute([$codigo, $_POST['matricula']]);
                    $message = 'Certificado creado exitosamente con código: ' . $codigo;
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE presentacion_certificado_curso SET PCC_ESTADO = 'I' WHERE PCC_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Certificado eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener matrículas para el select
$matriculas = $pdo->query("
    SELECT m.MAT_ID, m.MAT_CODIGO, u.US_NOMBRE, u.US_APELLIDO, c.CUR_NOMBRE
    FROM matricula m
    INNER JOIN usuario u ON m.US_ID = u.US_ID
    INNER JOIN curso c ON m.CUR_ID = c.CUR_ID
    WHERE m.MAT_ESTADO = 'A'
    ORDER BY m.MAT_ID DESC
")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND (pcc.PCC_CODIGO_UNICO LIKE ? OR u.US_CEDULA LIKE ? OR u.US_NOMBRE LIKE ?)" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM presentacion_certificado_curso pcc
    INNER JOIN matricula m ON pcc.MAT_ID = m.MAT_ID
    INNER JOIN usuario u ON m.US_ID = u.US_ID
    WHERE pcc.PCC_ESTADO = 'A' $searchQuery
");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT pcc.*, 
           u.US_CEDULA, u.US_NOMBRE, u.US_APELLIDO,
           c.CUR_NOMBRE,
           m.MAT_CODIGO, m.MAT_FECHA_INICIAL, m.MAT_FECHA_FINAL
    FROM presentacion_certificado_curso pcc
    INNER JOIN matricula m ON pcc.MAT_ID = m.MAT_ID
    INNER JOIN usuario u ON m.US_ID = u.US_ID
    INNER JOIN curso c ON m.CUR_ID = c.CUR_ID
    WHERE pcc.PCC_ESTADO = 'A' $searchQuery
    ORDER BY pcc.PCC_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$certificados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Certificados - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Certificados de Curso</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Certificado</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por código, cédula o nombre..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_certificados.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código Certificado</th>
                        <th>Estudiante</th>
                        <th>Cédula</th>
                        <th>Curso</th>
                        <th>Matrícula</th>
                        <th>Vigencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados as $cert): ?>
                    <tr>
                        <td><strong style="font-family: monospace;"><?php echo htmlspecialchars($cert['PCC_CODIGO_UNICO']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cert['US_NOMBRE'] . ' ' . $cert['US_APELLIDO']); ?></td>
                        <td><?php echo htmlspecialchars($cert['US_CEDULA']); ?></td>
                        <td><?php echo htmlspecialchars($cert['CUR_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($cert['MAT_CODIGO']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($cert['MAT_FECHA_INICIAL'])) . ' - ' . date('d/m/Y', strtotime($cert['MAT_FECHA_FINAL'])); ?></td>
                        <td class="actions">
                            <?php if ($cert['PCC_TOKEN']): ?>
                            <a href="ver_certificado.php?token=<?php echo $cert['PCC_TOKEN']; ?>" target="_blank" class="btn-icon" style="background: rgba(59, 130, 246, 0.1);" title="Ver">👁️</a>
                            <?php endif; ?>
                            <button class="btn-icon btn-delete" onclick="deleteCertificado(<?php echo $cert['PCC_ID']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($certificados)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron certificados</td>
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

    <div id="certificadoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Certificado</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="certificadoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Matrícula *</label>
                        <select name="matricula" id="matricula" required style="width: 100%;">
                            <option value="">Seleccione una matrícula...</option>
                            <?php foreach ($matriculas as $mat): ?>
                            <option value="<?php echo $mat['MAT_ID']; ?>">
                                Matrícula #<?php echo htmlspecialchars($mat['MAT_CODIGO']); ?> - 
                                <?php echo htmlspecialchars($mat['US_APELLIDO'] . ' ' . $mat['US_NOMBRE']); ?> - 
                                <?php echo htmlspecialchars($mat['CUR_NOMBRE']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <p style="color: #6b7280; font-size: 14px; margin-top: 10px;">
                            ℹ️ El código del certificado se generará automáticamente al crear el registro.
                        </p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Certificado</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('certificadoModal').classList.add('active');
            document.getElementById('certificadoForm').reset();
        }

        function closeModal() {
            document.getElementById('certificadoModal').classList.remove('active');
        }

        function deleteCertificado(id) {
            if (confirm('¿Está seguro de que desea eliminar este certificado?')) {
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
            const modal = document.getElementById('certificadoModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>