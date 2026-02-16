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
                        INSERT INTO lugar_examinacion (LDE_NOMBRE, LDE_ESTADO, PRR_ID) 
                        VALUES (?, 'A', ?)
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['parroquia']
                    ]);
                    $message = 'Lugar de examinación creado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE lugar_examinacion SET
                            LDE_NOMBRE = ?, PRR_ID = ?
                        WHERE LDE_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['parroquia'],
                        $_POST['id']
                    ]);
                    $message = 'Lugar de examinación actualizado exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE lugar_examinacion SET LDE_ESTADO = 'I' WHERE LDE_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Lugar de examinación eliminado exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener parroquias con su información completa para el select
$parroquias = $pdo->query("
    SELECT prr.PRR_ID, prr.PRR_NOMBRE, c.CAN_NOMBRE, pr.PR_NOMBRE 
    FROM parroquia prr 
    INNER JOIN canton c ON prr.CAN_ID = c.CAN_ID
    INNER JOIN provincia pr ON c.PR_ID = pr.PR_ID
    WHERE prr.PRR_ESTADO = 'A' 
    ORDER BY pr.PR_NOMBRE, c.CAN_NOMBRE, prr.PRR_NOMBRE
")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND l.LDE_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM lugar_examinacion l WHERE l.LDE_ESTADO = 'A' $searchQuery
");
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam]);
} else {
    $countStmt->execute();
}
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT l.*, prr.PRR_NOMBRE, c.CAN_NOMBRE, pr.PR_NOMBRE, ps.PS_NOMBRE,
           COUNT(r.RGO_ID) as total_registros
    FROM lugar_examinacion l
    INNER JOIN parroquia prr ON l.PRR_ID = prr.PRR_ID
    INNER JOIN canton c ON prr.CAN_ID = c.CAN_ID
    INNER JOIN provincia pr ON c.PR_ID = pr.PR_ID
    INNER JOIN pais ps ON pr.PS_ID = ps.PS_ID
    LEFT JOIN registro_oec r ON l.LDE_ID = r.LDE_ID AND r.RGO_ESTADO = 'A'
    WHERE l.LDE_ESTADO = 'A' $searchQuery
    GROUP BY l.LDE_ID
    ORDER BY l.LDE_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$lugares = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Lugares de Examinación - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Lugares de Examinación</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nuevo Lugar</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar lugar de examinación..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_lugares.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Lugar</th>
                        <th>Parroquia</th>
                        <th>Cantón</th>
                        <th>Provincia</th>
                        <th>Registros OEC</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lugares as $lugar): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lugar['LDE_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($lugar['LDE_NOMBRE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($lugar['PRR_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($lugar['CAN_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($lugar['PR_NOMBRE']); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($lugar['total_registros']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editLugar(<?php echo json_encode($lugar); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteLugar(<?php echo $lugar['LDE_ID']; ?>, <?php echo $lugar['total_registros']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lugares)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <div style="padding: 40px; text-align: center;">
                                <div style="font-size: 48px; margin-bottom: 16px;">📍</div>
                                <p style="color: #666; font-size: 16px; margin-bottom: 8px;">No hay lugares de examinación registrados</p>
                                <p style="color: #999; font-size: 14px;">Crea el primer lugar haciendo clic en "Nuevo Lugar"</p>
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

    <div id="lugarModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Lugar de Examinación</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="lugarForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="lugarId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre del Lugar *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Centro de Capacitación CPATEEC, Auditorio Municipal">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Ingrese el nombre completo del lugar donde se realizarán las examinaciones
                        </small>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Parroquia *</label>
                        <select name="parroquia" id="parroquia" required style="width: 100%;">
                            <option value="">Seleccione una parroquia...</option>
                            <?php 
                            $currentProvincia = '';
                            foreach ($parroquias as $p): 
                                if ($currentProvincia !== $p['PR_NOMBRE']) {
                                    if ($currentProvincia !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($p['PR_NOMBRE']) . '">';
                                    $currentProvincia = $p['PR_NOMBRE'];
                                }
                            ?>
                            <option value="<?php echo $p['PRR_ID']; ?>">
                                <?php echo htmlspecialchars($p['PRR_NOMBRE'] . ' - ' . $p['CAN_NOMBRE']); ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if ($currentProvincia !== '') echo '</optgroup>'; ?>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            📍 Seleccione la parroquia donde se ubica el lugar de examinación
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
            document.getElementById('lugarModal').classList.add('active');
            document.getElementById('lugarForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nuevo Lugar de Examinación';
        }

        function closeModal() {
            document.getElementById('lugarModal').classList.remove('active');
        }

        function editLugar(lugar) {
            document.getElementById('lugarModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Lugar de Examinación';
            document.getElementById('lugarId').value = lugar.LDE_ID;
            document.getElementById('nombre').value = lugar.LDE_NOMBRE;
            document.getElementById('parroquia').value = lugar.PRR_ID;
        }

        function deleteLugar(id, totalRegistros) {
            if (totalRegistros > 0) {
                if (!confirm(`¡ADVERTENCIA! Este lugar tiene ${totalRegistros} registro(s) OEC asociado(s).\n\n¿Está seguro de que desea eliminarlo? Esto solo lo marcará como inactivo.`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar este lugar de examinación?')) {
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
            const modal = document.getElementById('lugarModal');
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
    </script>
</body>
</html>