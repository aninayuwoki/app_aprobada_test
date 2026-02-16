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
                    // Generar código único de matrícula
                    $codigo = rand(100000, 999999);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO matricula (MAT_CODIGO, MAT_FECHA_INICIAL, MAT_FECHA_FINAL, MAT_ESTADO, US_ID, CUR_ID, CTT_ID, EMP_ID, HC_ID) 
                        VALUES (?, ?, ?, 'A', ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $codigo,
                        $_POST['fecha_inicial'],
                        $_POST['fecha_final'],
                        $_POST['usuario'],
                        $_POST['curso'],
                        $_POST['contrato'],
                        $_POST['empresa'],
                        $_POST['horario']
                    ]);
                    $message = 'Matrícula creada exitosamente con código: ' . $codigo;
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE matricula SET
                            MAT_FECHA_INICIAL = ?, MAT_FECHA_FINAL = ?, 
                            US_ID = ?, CUR_ID = ?, CTT_ID = ?, EMP_ID = ?, HC_ID = ?
                        WHERE MAT_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['fecha_inicial'],
                        $_POST['fecha_final'],
                        $_POST['usuario'],
                        $_POST['curso'],
                        $_POST['contrato'],
                        $_POST['empresa'],
                        $_POST['horario'],
                        $_POST['id']
                    ]);
                    $message = 'Matrícula actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE matricula SET MAT_ESTADO = 'I' WHERE MAT_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Matrícula eliminada exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener listas para selects
$usuarios = $pdo->query("SELECT US_ID, US_CEDULA, US_NOMBRE, US_APELLIDO FROM usuario WHERE US_ESTADO = 'A' ORDER BY US_APELLIDO")->fetchAll();
$cursos = $pdo->query("SELECT CUR_ID, CUR_NOMBRE FROM curso WHERE CUR_ESTADO = 'A' ORDER BY CUR_NOMBRE")->fetchAll();
$empresas = $pdo->query("SELECT EMP_ID, EMP_NOMBRE FROM empresa WHERE EMP_ESTADO = 'A' ORDER BY EMP_NOMBRE")->fetchAll();
$contratos = $pdo->query("SELECT CTT_ID, CTT_CODIGO FROM contrato WHERE CTT_ESTADO = 'A' ORDER BY CTT_CODIGO")->fetchAll();
$horarios = $pdo->query("SELECT HC_ID, HC_DESCRIPCION FROM horario_de_curso WHERE HC_ESTADO = 'A' ORDER BY HC_DESCRIPCION")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND (u.US_CEDULA LIKE ? OR u.US_NOMBRE LIKE ? OR u.US_APELLIDO LIKE ? OR m.MAT_CODIGO LIKE ?)" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM matricula m
    INNER JOIN usuario u ON m.US_ID = u.US_ID
    WHERE m.MAT_ESTADO = 'A' $searchQuery
");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT m.*, 
           u.US_CEDULA, u.US_NOMBRE, u.US_APELLIDO,
           c.CUR_NOMBRE,
           e.EMP_NOMBRE,
           hc.HC_DESCRIPCION
    FROM matricula m
    INNER JOIN usuario u ON m.US_ID = u.US_ID
    INNER JOIN curso c ON m.CUR_ID = c.CUR_ID
    INNER JOIN empresa e ON m.EMP_ID = e.EMP_ID
    LEFT JOIN horario_de_curso hc ON m.HC_ID = hc.HC_ID
    WHERE m.MAT_ESTADO = 'A' $searchQuery
    ORDER BY m.MAT_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$matriculas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Matrículas - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Matrículas</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Matrícula</button>
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
                <a href="admin_matriculas.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Estudiante</th>
                        <th>Cédula</th>
                        <th>Curso</th>
                        <th>Empresa</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matriculas as $mat): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($mat['MAT_CODIGO']); ?></strong></td>
                        <td><?php echo htmlspecialchars($mat['US_NOMBRE'] . ' ' . $mat['US_APELLIDO']); ?></td>
                        <td><?php echo htmlspecialchars($mat['US_CEDULA']); ?></td>
                        <td><?php echo htmlspecialchars($mat['CUR_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($mat['EMP_NOMBRE']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($mat['MAT_FECHA_INICIAL'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($mat['MAT_FECHA_FINAL'])); ?></td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editMatricula(<?php echo json_encode($mat); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteMatricula(<?php echo $mat['MAT_ID']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($matriculas)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron matrículas</td>
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

    <div id="matriculaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Matrícula</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="matriculaForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="matriculaId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Usuario/Estudiante *</label>
                        <select name="usuario" id="usuario" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo $u['US_ID']; ?>">
                                <?php echo htmlspecialchars($u['US_CEDULA'] . ' - ' . $u['US_APELLIDO'] . ' ' . $u['US_NOMBRE']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Curso *</label>
                        <select name="curso" id="curso" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($cursos as $c): ?>
                            <option value="<?php echo $c['CUR_ID']; ?>"><?php echo htmlspecialchars($c['CUR_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Empresa *</label>
                        <select name="empresa" id="empresa" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['EMP_ID']; ?>"><?php echo htmlspecialchars($emp['EMP_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Horario *</label>
                        <select name="horario" id="horario" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($horarios as $h): ?>
                            <option value="<?php echo $h['HC_ID']; ?>"><?php echo htmlspecialchars($h['HC_DESCRIPCION']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Contrato *</label>
                        <select name="contrato" id="contrato" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($contratos as $ctt): ?>
                            <option value="<?php echo $ctt['CTT_ID']; ?>">Contrato #<?php echo htmlspecialchars($ctt['CTT_CODIGO']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Inicial *</label>
                        <input type="date" name="fecha_inicial" id="fecha_inicial" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Final *</label>
                        <input type="date" name="fecha_final" id="fecha_final" required>
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
            document.getElementById('matriculaModal').classList.add('active');
            document.getElementById('matriculaForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Matrícula';
        }

        function closeModal() {
            document.getElementById('matriculaModal').classList.remove('active');
        }

        function editMatricula(mat) {
            document.getElementById('matriculaModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Matrícula';
            document.getElementById('matriculaId').value = mat.MAT_ID;
            document.getElementById('usuario').value = mat.US_ID;
            document.getElementById('curso').value = mat.CUR_ID;
            document.getElementById('empresa').value = mat.EMP_ID;
            document.getElementById('horario').value = mat.HC_ID;
            document.getElementById('contrato').value = mat.CTT_ID;
            document.getElementById('fecha_inicial').value = mat.MAT_FECHA_INICIAL;
            document.getElementById('fecha_final').value = mat.MAT_FECHA_FINAL;
        }

        function deleteMatricula(id) {
            if (confirm('¿Está seguro de que desea eliminar esta matrícula?')) {
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
            const modal = document.getElementById('matriculaModal');
            if (event.target === modal) closeModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeModal();
        });

        // Validar que fecha final sea mayor a fecha inicial
        document.getElementById('fecha_final').addEventListener('change', function() {
            const fechaInicial = document.getElementById('fecha_inicial').value;
            const fechaFinal = this.value;
            if (fechaInicial && fechaFinal && fechaFinal < fechaInicial) {
                alert('La fecha final debe ser posterior a la fecha inicial');
                this.value = '';
            }
        });
    </script>
</body>
</html>