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
                    $codigo = 'OEC-' . date('Y') . '-' . rand(10000, 99999);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO presentacion_certificado_oec 
                        (POEC_CODIGO_CERTIFICACION, POEC_F_INICIO, POEC_F_FIN, POEC_ESTADO, EMP_ID, RGO_ID) 
                        VALUES (?, ?, ?, 'A', ?, ?)
                    ");
                    $stmt->execute([
                        $codigo,
                        $_POST['fecha_inicio'],
                        $_POST['fecha_fin'],
                        $_POST['empresa'],
                        $_POST['registro']
                    ]);
                    $message = 'Certificado OEC creado exitosamente con código: ' . $codigo;
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE presentacion_certificado_oec SET
                            POEC_F_INICIO = ?, POEC_F_FIN = ?, EMP_ID = ?, RGO_ID = ?
                        WHERE POEC_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['fecha_inicio'],
                        $_POST['fecha_fin'],
                        $_POST['empresa'],
                        $_POST['registro'],
                        $_POST['id']
                    ]);
                    $message = 'Certificado OEC actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE presentacion_certificado_oec SET POEC_ESTADO = 'I' WHERE POEC_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Certificado OEC eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener empresas y registros para selects
$empresas = $pdo->query("SELECT EMP_ID, EMP_NOMBRE FROM empresa WHERE EMP_ESTADO = 'A' ORDER BY EMP_NOMBRE")->fetchAll();
$registros = $pdo->query("
    SELECT r.RGO_ID, r.RGO_CODIGO_FORMULARIO, u.US_NOMBRE, u.US_APELLIDO, c.CON_NOMBRE
    FROM registro_oec r
    INNER JOIN usuario u ON r.US_ID = u.US_ID
    INNER JOIN certificaciones_oec c ON r.CON_ID = c.CON_ID
    WHERE r.RGO_ESTADO = 'A'
    ORDER BY r.RGO_ID DESC
")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND (poec.POEC_CODIGO_CERTIFICACION LIKE ? OR u.US_CEDULA LIKE ? OR u.US_NOMBRE LIKE ?)" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM presentacion_certificado_oec poec
    INNER JOIN registro_oec r ON poec.RGO_ID = r.RGO_ID
    INNER JOIN usuario u ON r.US_ID = u.US_ID
    WHERE poec.POEC_ESTADO = 'A' $searchQuery
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
    SELECT poec.*, 
           u.US_CEDULA, u.US_NOMBRE, u.US_APELLIDO,
           e.EMP_NOMBRE,
           c.CON_NOMBRE,
           r.RGO_CODIGO_FORMULARIO
    FROM presentacion_certificado_oec poec
    INNER JOIN registro_oec r ON poec.RGO_ID = r.RGO_ID
    INNER JOIN usuario u ON r.US_ID = u.US_ID
    INNER JOIN empresa e ON poec.EMP_ID = e.EMP_ID
    INNER JOIN certificaciones_oec c ON r.CON_ID = c.CON_ID
    WHERE poec.POEC_ESTADO = 'A' $searchQuery
    ORDER BY poec.POEC_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$certificados_oec = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Certificados OEC - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Certificados OEC</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Certificado OEC</button>
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
                <a href="admin_oec.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código Certificación</th>
                        <th>Persona</th>
                        <th>Cédula</th>
                        <th>Certificación</th>
                        <th>Empresa</th>
                        <th>Vigencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados_oec as $cert): ?>
                    <tr>
                        <td><strong style="font-family: monospace;"><?php echo htmlspecialchars($cert['POEC_CODIGO_CERTIFICACION']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cert['US_NOMBRE'] . ' ' . $cert['US_APELLIDO']); ?></td>
                        <td><?php echo htmlspecialchars($cert['US_CEDULA']); ?></td>
                        <td><?php echo htmlspecialchars($cert['CON_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($cert['EMP_NOMBRE']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($cert['POEC_F_INICIO'])) . ' - ' . date('d/m/Y', strtotime($cert['POEC_F_FIN'])); ?></td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editOEC(<?php echo json_encode($cert); ?>)' title="Editar">✏️</button>
                            <?php if ($cert['POEC_TOKEN']): ?>
                            <a href="ver_certificado.php?token=<?php echo $cert['POEC_TOKEN']; ?>" target="_blank" class="btn-icon" style="background: rgba(59, 130, 246, 0.1);" title="Ver">👁️</a>
                            <?php endif; ?>
                            <button class="btn-icon btn-delete" onclick="deleteOEC(<?php echo $cert['POEC_ID']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($certificados_oec)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron certificados OEC</td>
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

    <div id="oecModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Certificado OEC</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="oecForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="oecId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Registro OEC *</label>
                        <select name="registro" id="registro" required>
                            <option value="">Seleccione un registro...</option>
                            <?php foreach ($registros as $reg): ?>
                            <option value="<?php echo $reg['RGO_ID']; ?>">
                                <?php echo htmlspecialchars($reg['RGO_CODIGO_FORMULARIO']); ?> - 
                                <?php echo htmlspecialchars($reg['US_APELLIDO'] . ' ' . $reg['US_NOMBRE']); ?> - 
                                <?php echo htmlspecialchars($reg['CON_NOMBRE']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Empresa Certificadora *</label>
                        <select name="empresa" id="empresa" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['EMP_ID']; ?>"><?php echo htmlspecialchars($emp['EMP_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Inicio Vigencia *</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Fin Vigencia *</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" required>
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
            document.getElementById('oecModal').classList.add('active');
            document.getElementById('oecForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Certificado OEC';
        }

        function closeModal() {
            document.getElementById('oecModal').classList.remove('active');
        }

        function editOEC(cert) {
            document.getElementById('oecModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Certificado OEC';
            document.getElementById('oecId').value = cert.POEC_ID;
            document.getElementById('registro').value = cert.RGO_ID;
            document.getElementById('empresa').value = cert.EMP_ID;
            document.getElementById('fecha_inicio').value = cert.POEC_F_INICIO;
            document.getElementById('fecha_fin').value = cert.POEC_F_FIN;
        }

        function deleteOEC(id) {
            if (confirm('¿Está seguro de que desea eliminar este certificado OEC?')) {
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
            const modal = document.getElementById('oecModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });

        // Validar fechas
        document.getElementById('fecha_fin').addEventListener('change', function() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = this.value;
            if (fechaInicio && fechaFin && fechaFin < fechaInicio) {
                alert('La fecha de fin debe ser posterior a la fecha de inicio');
                this.value = '';
            }
        });
    </script>
</body>
</html>