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
                    $stmt = $pdo->prepare("INSERT INTO horario_de_curso (HC_DESCRIPCION, HC_ESTADO) VALUES (?, 'A')");
                    $stmt->execute([$_POST['descripcion']]);
                    $message = 'Horario creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE horario_de_curso SET HC_DESCRIPCION = ? WHERE HC_ID = ?");
                    $stmt->execute([$_POST['descripcion'], $_POST['id']]);
                    $message = 'Horario actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE horario_de_curso SET HC_ESTADO = 'I' WHERE HC_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Horario eliminado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'create_detalle':
                    $stmt = $pdo->prepare("
                        INSERT INTO detalle_horario (DH_HORA_INICIO, DH_HORA_FIN, DH_DIA, DH_ESTADO, HC_ID) 
                        VALUES (?, ?, ?, 'A', ?)
                    ");
                    $stmt->execute([
                        $_POST['hora_inicio'],
                        $_POST['hora_fin'],
                        $_POST['dia'],
                        $_POST['horario_id']
                    ]);
                    $message = 'Detalle de horario agregado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete_detalle':
                    $stmt = $pdo->prepare("UPDATE detalle_horario SET DH_ESTADO = 'I' WHERE DH_ID = ?");
                    $stmt->execute([$_POST['detalle_id']]);
                    $message = 'Detalle eliminado exitosamente';
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
$searchQuery = $search ? "AND HC_DESCRIPCION LIKE ?" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM horario_de_curso WHERE HC_ESTADO = 'A' $searchQuery");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT h.*, COUNT(m.MAT_ID) as total_matriculas
    FROM horario_de_curso h
    LEFT JOIN matricula m ON h.HC_ID = m.HC_ID AND m.MAT_ESTADO = 'A'
    WHERE h.HC_ESTADO = 'A' $searchQuery
    GROUP BY h.HC_ID
    ORDER BY h.HC_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$horarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Horarios de Curso</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Horario</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar horario..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_horarios.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Matrículas</th>
                        <th>Detalles</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $horario): ?>
                    <?php
                    // Obtener detalles del horario
                    $stmtDetalles = $pdo->prepare("
                        SELECT * FROM detalle_horario 
                        WHERE HC_ID = ? AND DH_ESTADO = 'A'
                        ORDER BY 
                            CASE DH_DIA
                                WHEN 'Lunes' THEN 1
                                WHEN 'Martes' THEN 2
                                WHEN 'Miércoles' THEN 3
                                WHEN 'Jueves' THEN 4
                                WHEN 'Viernes' THEN 5
                                WHEN 'Sábado' THEN 6
                                WHEN 'Domingo' THEN 7
                            END, DH_HORA_INICIO
                    ");
                    $stmtDetalles->execute([$horario['HC_ID']]);
                    $detalles = $stmtDetalles->fetchAll();
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($horario['HC_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($horario['HC_DESCRIPCION']); ?></strong></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($horario['total_matriculas']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-secondary" style="font-size: 12px; padding: 4px 8px;" onclick="verDetalles(<?php echo $horario['HC_ID']; ?>, '<?php echo htmlspecialchars(addslashes($horario['HC_DESCRIPCION'])); ?>')">
                                👁️ Ver (<?php echo count($detalles); ?>)
                            </button>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editHorario(<?php echo json_encode($horario); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon" style="background: rgba(59, 130, 246, 0.1);" onclick="agregarDetalle(<?php echo $horario['HC_ID']; ?>)" title="Agregar Detalle">➕</button>
                            <button class="btn-icon btn-delete" onclick="deleteHorario(<?php echo $horario['HC_ID']; ?>, <?php echo $horario['total_matriculas']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($horarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron horarios</td>
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

    <!-- Modal Horario -->
    <div id="horarioModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Horario</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="horarioForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="horarioId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Descripción del Horario *</label>
                        <input type="text" name="descripcion" id="descripcion" required 
                               placeholder="Ej: Matutino, Vespertino, Nocturno, Fin de Semana">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div id="detalleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detalleModalTitle">Agregar Detalle de Horario</h2>
                <span class="close" onclick="closeDetalleModal()">&times;</span>
            </div>
            <form method="POST" id="detalleForm">
                <input type="hidden" name="action" value="create_detalle">
                <input type="hidden" name="horario_id" id="detalleHorarioId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Día *</label>
                        <select name="dia" id="dia" required>
                            <option value="">Seleccione...</option>
                            <option value="Lunes">Lunes</option>
                            <option value="Martes">Martes</option>
                            <option value="Miércoles">Miércoles</option>
                            <option value="Jueves">Jueves</option>
                            <option value="Viernes">Viernes</option>
                            <option value="Sábado">Sábado</option>
                            <option value="Domingo">Domingo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Hora Inicio *</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Hora Fin *</label>
                        <input type="time" name="hora_fin" id="hora_fin" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDetalleModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div id="verDetallesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="verDetallesTitle">Detalles del Horario</h2>
                <span class="close" onclick="closeVerDetallesModal()">&times;</span>
            </div>
            <div id="detallesContent" style="padding: 20px;">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeVerDetallesModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('horarioModal').classList.add('active');
            document.getElementById('horarioForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Horario';
        }

        function closeModal() {
            document.getElementById('horarioModal').classList.remove('active');
        }

        function editHorario(horario) {
            document.getElementById('horarioModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Horario';
            document.getElementById('horarioId').value = horario.HC_ID;
            document.getElementById('descripcion').value = horario.HC_DESCRIPCION;
        }

        function deleteHorario(id, totalMatriculas) {
            if (totalMatriculas > 0) {
                if (!confirm(`¡ADVERTENCIA! Este horario tiene ${totalMatriculas} matrícula(s) asociada(s).\n\n¿Está seguro de que desea eliminarlo?`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este horario?')) {
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

        function agregarDetalle(horarioId) {
            document.getElementById('detalleModal').classList.add('active');
            document.getElementById('detalleHorarioId').value = horarioId;
            document.getElementById('detalleForm').reset();
        }

        function closeDetalleModal() {
            document.getElementById('detalleModal').classList.remove('active');
        }

        async function verDetalles(horarioId, descripcion) {
            document.getElementById('verDetallesModal').classList.add('active');
            document.getElementById('verDetallesTitle').textContent = `Detalles: ${descripcion}`;
            
            // Cargar detalles via AJAX
            const response = await fetch(`ajax_get_detalles_horario.php?id=${horarioId}`);
            const data = await response.json();
            
            let html = '<table class="data-table" style="width: 100%;">';
            html += '<thead><tr><th>Día</th><th>Hora Inicio</th><th>Hora Fin</th><th>Acciones</th></tr></thead><tbody>';
            
            if (data.detalles && data.detalles.length > 0) {
                data.detalles.forEach(detalle => {
                    html += `<tr>
                        <td><strong>${detalle.DH_DIA}</strong></td>
                        <td>${detalle.DH_HORA_INICIO}</td>
                        <td>${detalle.DH_HORA_FIN}</td>
                        <td>
                            <button class="btn-icon btn-delete" onclick="deleteDetalle(${detalle.DH_ID})" title="Eliminar">🗑️</button>
                        </td>
                    </tr>`;
                });
            } else {
                html += '<tr><td colspan="4" class="text-center">No hay detalles agregados</td></tr>';
            }
            
            html += '</tbody></table>';
            html += `<br><button class="btn btn-primary" onclick="closeVerDetallesModal(); agregarDetalle(${horarioId});">➕ Agregar Detalle</button>`;
            
            document.getElementById('detallesContent').innerHTML = html;
        }

        function closeVerDetallesModal() {
            document.getElementById('verDetallesModal').classList.remove('active');
        }

        function deleteDetalle(detalleId) {
            if (confirm('¿Está seguro de que desea eliminar este detalle?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_detalle">
                    <input type="hidden" name="detalle_id" value="${detalleId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Validar que hora fin sea mayor que hora inicio
        document.getElementById('hora_fin')?.addEventListener('change', function() {
            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFin = this.value;
            if (horaInicio && horaFin && horaFin <= horaInicio) {
                alert('La hora de fin debe ser posterior a la hora de inicio');
                this.value = '';
            }
        });
    </script>
</body>
</html>