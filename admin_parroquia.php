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
                        INSERT INTO parroquia (PRR_NOMBRE, PRR_DIRECCION, PRR_ESTADO, CAN_ID) 
                        VALUES (?, ?, 'A', ?)
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['direccion'],
                        $_POST['canton']
                    ]);
                    $message = 'Parroquia creada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE parroquia SET
                            PRR_NOMBRE = ?, PRR_DIRECCION = ?, CAN_ID = ?
                        WHERE PRR_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['direccion'],
                        $_POST['canton'],
                        $_POST['id']
                    ]);
                    $message = 'Parroquia actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE parroquia SET PRR_ESTADO = 'I' WHERE PRR_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Parroquia eliminada exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener cantones con su información completa para el select
$cantones = $pdo->query("
    SELECT c.CAN_ID, c.CAN_NOMBRE, pr.PR_NOMBRE, ps.PS_NOMBRE 
    FROM canton c 
    INNER JOIN provincia pr ON c.PR_ID = pr.PR_ID
    INNER JOIN pais ps ON pr.PS_ID = ps.PS_ID
    WHERE c.CAN_ESTADO = 'A' 
    ORDER BY pr.PR_NOMBRE, c.CAN_NOMBRE
")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND prr.PRR_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM parroquia prr WHERE prr.PRR_ESTADO = 'A' $searchQuery
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
    SELECT prr.*, c.CAN_NOMBRE, pr.PR_NOMBRE, ps.PS_NOMBRE,
           COUNT(DISTINCT lde.LDE_ID) as total_lugares,
           COUNT(DISTINCT inf.INF_ID) as total_instrucciones
    FROM parroquia prr
    INNER JOIN canton c ON prr.CAN_ID = c.CAN_ID
    INNER JOIN provincia pr ON c.PR_ID = pr.PR_ID
    INNER JOIN pais ps ON pr.PS_ID = ps.PS_ID
    LEFT JOIN lugar_examinacion lde ON prr.PRR_ID = lde.PRR_ID AND lde.LDE_ESTADO = 'A'
    LEFT JOIN instruccion_formal inf ON prr.PRR_ID = inf.PRR_ID AND inf.INF_ESTADO = 'A'
    WHERE prr.PRR_ESTADO = 'A' $searchQuery
    GROUP BY prr.PRR_ID
    ORDER BY prr.PRR_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$parroquias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Parroquias - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Parroquias</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Parroquia</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar parroquia..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_parroquia.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Parroquia</th>
                        <th>Dirección</th>
                        <th>Cantón</th>
                        <th>Provincia</th>
                        <th>Lugares</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parroquias as $parroquia): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($parroquia['PRR_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($parroquia['PRR_NOMBRE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($parroquia['PRR_DIRECCION']); ?></td>
                        <td><?php echo htmlspecialchars($parroquia['CAN_NOMBRE']); ?></td>
                        <td><?php echo htmlspecialchars($parroquia['PR_NOMBRE']); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($parroquia['total_lugares']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editParroquia(<?php echo json_encode($parroquia); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteParroquia(<?php echo $parroquia['PRR_ID']; ?>, <?php echo $parroquia['total_lugares']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($parroquias)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <div style="padding: 40px; text-align: center;">
                                <div style="font-size: 48px; margin-bottom: 16px;">🏘️</div>
                                <p style="color: #666; font-size: 16px; margin-bottom: 8px;">No hay parroquias registradas</p>
                                <p style="color: #999; font-size: 14px;">Crea la primera parroquia haciendo clic en "Nueva Parroquia"</p>
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

    <div id="parroquiaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Parroquia</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="parroquiaForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="parroquiaId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Cantón *</label>
                        <select name="canton" id="canton" required style="width: 100%;">
                            <option value="">Seleccione un cantón...</option>
                            <?php 
                            $currentProvincia = '';
                            foreach ($cantones as $c): 
                                if ($currentProvincia !== $c['PR_NOMBRE']) {
                                    if ($currentProvincia !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($c['PR_NOMBRE']) . ' - ' . htmlspecialchars($c['PS_NOMBRE']) . '">';
                                    $currentProvincia = $c['PR_NOMBRE'];
                                }
                            ?>
                            <option value="<?php echo $c['CAN_ID']; ?>">
                                <?php echo htmlspecialchars($c['CAN_NOMBRE']); ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if ($currentProvincia !== '') echo '</optgroup>'; ?>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            📍 Seleccione el cantón al que pertenece la parroquia
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre de la Parroquia *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Tarqui, Urdesa, García Moreno">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Ingrese el nombre oficial de la parroquia
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>Dirección *</label>
                        <input type="text" name="direccion" id="direccion" required 
                               placeholder="Ej: Av. Principal y Calle Secundaria">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            🏢 Dirección de referencia de la parroquia
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
            document.getElementById('parroquiaModal').classList.add('active');
            document.getElementById('parroquiaForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Parroquia';
        }

        function closeModal() {
            document.getElementById('parroquiaModal').classList.remove('active');
        }

        function editParroquia(parroquia) {
            document.getElementById('parroquiaModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Parroquia';
            document.getElementById('parroquiaId').value = parroquia.PRR_ID;
            document.getElementById('nombre').value = parroquia.PRR_NOMBRE;
            document.getElementById('direccion').value = parroquia.PRR_DIRECCION;
            document.getElementById('canton').value = parroquia.CAN_ID;
        }

        function deleteParroquia(id, totalLugares) {
            if (totalLugares > 0) {
                if (!confirm(`¡ADVERTENCIA! Esta parroquia tiene ${totalLugares} lugar(es) de examinación asociado(s).\n\n¿Está seguro de que desea eliminarla? Esto solo la marcará como inactiva.`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar esta parroquia?')) {
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
            const modal = document.getElementById('parroquiaModal');
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