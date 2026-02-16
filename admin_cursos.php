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
                        INSERT INTO curso (CUR_NOMBRE, CUR_HORA_TOTAL, CUR_ESTADO, COS_ID, CCO_ID, RQT_ID, MDL_ID) 
                        VALUES (?, ?, 'A', ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['horas'],
                        $_POST['costo'],
                        $_POST['categoria'],
                        $_POST['requisito'],
                        $_POST['modalidad']
                    ]);
                    $message = 'Curso creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE curso SET
                            CUR_NOMBRE = ?, CUR_HORA_TOTAL = ?, COS_ID = ?, CCO_ID = ?, RQT_ID = ?, MDL_ID = ?
                        WHERE CUR_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['horas'],
                        $_POST['costo'],
                        $_POST['categoria'],
                        $_POST['requisito'],
                        $_POST['modalidad'],
                        $_POST['id']
                    ]);
                    $message = 'Curso actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE curso SET CUR_ESTADO = 'I' WHERE CUR_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Curso eliminado exitosamente';
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
$categorias = $pdo->query("SELECT CCO_ID, CCO_NOMBRE FROM categoria_curso_y_oec WHERE CCO_ESTADO = 'A' ORDER BY CCO_NOMBRE")->fetchAll();
$costos = $pdo->query("SELECT COS_ID, COS_PAGO_TOTAL FROM costo WHERE COS_ESTADO = 'A' ORDER BY COS_PAGO_TOTAL")->fetchAll();
$requisitos = $pdo->query("SELECT RQT_ID, RQT_NOMBRE FROM requisitos_oec_cursos WHERE RQT_ESTADO = 'A' ORDER BY RQT_NOMBRE")->fetchAll();
$modalidades = $pdo->query("SELECT MDL_ID, MDL_NOMBRE FROM modalidad WHERE MDL_ESTADO = 'A' ORDER BY MDL_NOMBRE")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND (CUR_NOMBRE LIKE ?)" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM curso WHERE CUR_ESTADO = 'A' $searchQuery");
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
           cco.CCO_NOMBRE as categoria_nombre,
           cos.COS_PAGO_TOTAL as costo_total,
           mdl.MDL_NOMBRE as modalidad_nombre,
           rqt.RQT_NOMBRE as requisito_nombre,
           (SELECT COUNT(*) FROM matricula WHERE CUR_ID = c.CUR_ID AND MAT_ESTADO = 'A') as total_matriculas
    FROM curso c
    LEFT JOIN categoria_curso_y_oec cco ON c.CCO_ID = cco.CCO_ID
    LEFT JOIN costo cos ON c.COS_ID = cos.COS_ID
    LEFT JOIN modalidad mdl ON c.MDL_ID = mdl.MDL_ID
    LEFT JOIN requisitos_oec_cursos rqt ON c.RQT_ID = rqt.RQT_ID
    WHERE c.CUR_ESTADO = 'A' $searchQuery
    ORDER BY c.CUR_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$cursos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Cursos</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Curso</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por nombre del curso..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_cursos.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Curso</th>
                        <th>Categoría</th>
                        <th>Modalidad</th>
                        <th>Horas</th>
                        <th>Costo</th>
                        <th>Matrículas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($curso['CUR_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($curso['CUR_NOMBRE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($curso['categoria_nombre'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($curso['modalidad_nombre'] ?? 'N/A'); ?></td>
                        <td><span style="font-weight: 600; color: #023A60;"><?php echo htmlspecialchars($curso['CUR_HORA_TOTAL']); ?> hrs</span></td>
                        <td><span style="font-weight: 600; color: #10b981;">$<?php echo number_format($curso['costo_total'] ?? 0, 2); ?></span></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($curso['total_matriculas']); ?>
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editCurso(<?php echo json_encode($curso); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteCurso(<?php echo $curso['CUR_ID']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cursos)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron cursos</td>
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

    <div id="cursoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Curso</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="cursoForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="cursoId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre del Curso *</label>
                        <input type="text" name="nombre" id="nombre" required placeholder="Ej: Seguridad Industrial Básica">
                    </div>
                    
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select name="categoria" id="categoria" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['CCO_ID']; ?>"><?php echo htmlspecialchars($cat['CCO_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Modalidad *</label>
                        <select name="modalidad" id="modalidad" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($modalidades as $mod): ?>
                            <option value="<?php echo $mod['MDL_ID']; ?>"><?php echo htmlspecialchars($mod['MDL_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Horas Totales *</label>
                        <input type="number" name="horas" id="horas" required min="1" max="999" step="1" 
                               placeholder="Ej: 40">
                    </div>
                    
                    <div class="form-group">
                        <label>Costo *</label>
                        <select name="costo" id="costo" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($costos as $cos): ?>
                            <option value="<?php echo $cos['COS_ID']; ?>">$<?php echo number_format($cos['COS_PAGO_TOTAL'], 2); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Requisito *</label>
                        <select name="requisito" id="requisito" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($requisitos as $req): ?>
                            <option value="<?php echo $req['RQT_ID']; ?>"><?php echo htmlspecialchars($req['RQT_NOMBRE']); ?></option>
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
            document.getElementById('cursoModal').classList.add('active');
            document.getElementById('cursoForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Curso';
        }

        function closeModal() {
            document.getElementById('cursoModal').classList.remove('active');
        }

        function editCurso(curso) {
            document.getElementById('cursoModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Curso';
            document.getElementById('cursoId').value = curso.CUR_ID;
            document.getElementById('nombre').value = curso.CUR_NOMBRE;
            document.getElementById('horas').value = curso.CUR_HORA_TOTAL;
            document.getElementById('categoria').value = curso.CCO_ID;
            document.getElementById('modalidad').value = curso.MDL_ID;
            document.getElementById('costo').value = curso.COS_ID;
            document.getElementById('requisito').value = curso.RQT_ID;
        }

        function deleteCurso(id) {
            if (confirm('¿Está seguro de que desea eliminar este curso?\n\nNota: Esto solo lo marcará como inactivo, no afectará las matrículas existentes.')) {
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
            const modal = document.getElementById('cursoModal');
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

        // Capitalize first letter of course name
        document.getElementById('nombre').addEventListener('blur', function() {
            this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
        });
    </script>
</body>
</html>