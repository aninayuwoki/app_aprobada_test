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
                        INSERT INTO certificaciones_oec (CON_NOMBRE, CON_ESTADO, CCO_ID) 
                        VALUES (?, 'A', ?)
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['categoria']
                    ]);
                    $message = 'Certificación OEC creada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE certificaciones_oec SET
                            CON_NOMBRE = ?, CCO_ID = ?
                        WHERE CON_ID = ?
                    ");
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['categoria'],
                        $_POST['id']
                    ]);
                    $message = 'Certificación OEC actualizada exitosamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("UPDATE certificaciones_oec SET CON_ESTADO = 'I' WHERE CON_ID = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Certificación OEC eliminada exitosamente';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener categorías para el select
$categorias = $pdo->query("SELECT CCO_ID, CCO_NOMBRE FROM categoria_curso_y_oec WHERE CCO_ESTADO = 'A' ORDER BY CCO_NOMBRE")->fetchAll();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "AND c.CON_NOMBRE LIKE ?" : "";

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM certificaciones_oec c WHERE c.CON_ESTADO = 'A' $searchQuery
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
    SELECT c.*, cat.CCO_NOMBRE,
           COUNT(DISTINCT r.RGO_ID) as total_registros,
           COUNT(DISTINCT p.POEC_ID) as total_presentaciones
    FROM certificaciones_oec c
    LEFT JOIN categoria_curso_y_oec cat ON c.CCO_ID = cat.CCO_ID
    LEFT JOIN registro_oec r ON c.CON_ID = r.CON_ID AND r.RGO_ESTADO = 'A'
    LEFT JOIN presentacion_certificado_oec p ON r.RGO_ID = p.RGO_ID AND p.POEC_ESTADO = 'A'
    WHERE c.CON_ESTADO = 'A' $searchQuery
    GROUP BY c.CON_ID
    ORDER BY c.CON_ID DESC
    LIMIT $perPage OFFSET $offset
");
if ($search) {
    $stmt->execute([$searchParam]);
} else {
    $stmt->execute();
}
$certificaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Certificaciones OEC - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="capatec.ico">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="header-left">
                <a href="admin.php" class="back-btn">← Volver al Dashboard</a>
                <h1>Gestión de Certificaciones OEC</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openModal()">➕ Nueva Certificación</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar certificación OEC..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($search): ?>
                <a href="admin_certificaciones_oec.php" class="btn btn-secondary">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Certificación</th>
                        <th>Categoría</th>
                        <th>Registros</th>
                        <th>Certificados Emitidos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificaciones as $cert): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cert['CON_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($cert['CON_NOMBRE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cert['CCO_NOMBRE'] ?? 'Sin categoría'); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($cert['total_registros']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($cert['total_presentaciones']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                ✓ Activo
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon btn-edit" onclick='editCertificacion(<?php echo json_encode($cert); ?>)' title="Editar">✏️</button>
                            <button class="btn-icon btn-delete" onclick="deleteCertificacion(<?php echo $cert['CON_ID']; ?>, <?php echo $cert['total_registros']; ?>)" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($certificaciones)): ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <div style="padding: 40px; text-align: center;">
                                <div style="font-size: 48px; margin-bottom: 16px;">🏅</div>
                                <p style="color: #666; font-size: 16px; margin-bottom: 8px;">No hay certificaciones OEC registradas</p>
                                <p style="color: #999; font-size: 14px;">Crea la primera certificación haciendo clic en "Nueva Certificación"</p>
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

    <div id="certificacionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Certificación OEC</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="certificacionForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="certificacionId">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nombre de la Certificación *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               placeholder="Ej: Operador de Equipos Pesados, Inspector de Seguridad Industrial">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Ingrese el nombre completo de la certificación OEC
                        </small>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Categoría *</label>
                        <select name="categoria" id="categoria" required>
                            <option value="">Seleccione una categoría...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['CCO_ID']; ?>"><?php echo htmlspecialchars($cat['CCO_NOMBRE']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            📂 La categoría ayuda a organizar y clasificar las certificaciones
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
            document.getElementById('certificacionModal').classList.add('active');
            document.getElementById('certificacionForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Nueva Certificación OEC';
        }

        function closeModal() {
            document.getElementById('certificacionModal').classList.remove('active');
        }

        function editCertificacion(cert) {
            document.getElementById('certificacionModal').classList.add('active');
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Editar Certificación OEC';
            document.getElementById('certificacionId').value = cert.CON_ID;
            document.getElementById('nombre').value = cert.CON_NOMBRE;
            document.getElementById('categoria').value = cert.CCO_ID;
        }

        function deleteCertificacion(id, totalRegistros) {
            if (totalRegistros > 0) {
                if (!confirm(`¡ADVERTENCIA! Esta certificación tiene ${totalRegistros} registro(s) asociado(s).\n\n¿Está seguro de que desea eliminarla? Esto solo la marcará como inactiva.`)) {
                    return;
                }
            } else {
                if (!confirm('¿Está seguro de que desea eliminar esta certificación OEC?')) {
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
            const modal = document.getElementById('certificacionModal');
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