<?php
require_once '../db.php';

// No JSON header here by default, it depends on the action's output.

$action = $_GET['action'] ?? null;
$entity = $_GET['entity'] ?? null;

switch ($action) {
    case 'get_form':
        if (!$entity) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Entity not specified.']);
            exit;
        }
        header('Content-Type: text/html');
        echo getFormForEntity($entity, $pdo);
        break;

    case 'create':
        header('Content-Type: application/json');
        if (!$entity) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Entity not specified.']);
            exit;
        }
        $result = createEntity($entity, $_POST, $pdo);
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function getFormForEntity($entity, $pdo) {
    switch ($entity) {
        case 'instruccion_formal':
            $niveles = $pdo->query("SELECT NV_ID, NV_NOMBRE FROM niveles WHERE NV_ESTADO = 'A' ORDER BY NV_NOMBRE")->fetchAll(PDO::FETCH_ASSOC);
            $niveles_options = '';
            foreach ($niveles as $nivel) {
                $niveles_options .= "<option value=\"{$nivel['NV_ID']}\">" . htmlspecialchars($nivel['NV_NOMBRE']) . "</option>";
            }
             $parroquias = $pdo->query("SELECT PRR_ID, PRR_NOMBRE FROM parroquia WHERE PRR_ESTADO = 'A' ORDER BY PRR_NOMBRE")->fetchAll(PDO::FETCH_ASSOC);
            $parroquias_options = '';
            foreach ($parroquias as $parroquia) {
                $parroquias_options .= "<option value=\"{$parroquia['PRR_ID']}\">" . htmlspecialchars($parroquia['PRR_NOMBRE']) . "</option>";
            }
            return '
                <form>
                    <div class="form-group">
                        <label>Título/Nivel de Instrucción *</label>
                        <input type="text" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label>Institución *</label>
                        <input type="text" name="institucion" required>
                    </div>
                     <div class="form-group">
                        <label>Nivel *</label>
                        <select name="nivel" required>
                            ' . $niveles_options . '
                        </select>
                    </div>
                     <div class="form-group">
                        <label>Parroquia *</label>
                        <div class="input-with-button">
                            <select name="parroquia" id="parroquia-select-instruccion" required>
                                ' . $parroquias_options . '
                            </select>
                            <button type="button" class="btn-add" onclick="openModal(\'parroquia\', \'parroquia-select-instruccion\')">➕</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" onclick="closeModal(\'instruccion_formal\')">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            ';
        case 'pais':
            return '
                <form>
                    <div class="form-group">
                        <label>Nombre del País *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" onclick="closeModal(\'pais\')">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            ';
        case 'provincia':
            $paises = $pdo->query("SELECT PS_ID, PS_NOMBRE FROM pais WHERE PS_ESTADO = 'A' ORDER BY PS_NOMBRE")->fetchAll(PDO::FETCH_ASSOC);
            $pais_options = '';
            foreach ($paises as $pais) {
                $pais_options .= "<option value=\"{$pais['PS_ID']}\">" . htmlspecialchars($pais['PS_NOMBRE']) . "</option>";
            }
            return '
                <form>
                    <div class="form-group">
                        <label>Nombre de la Provincia *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>País *</label>
                        <div class="input-with-button">
                            <select name="pais" id="pais-select-provincia" required>
                                ' . $pais_options . '
                            </select>
                            <button type="button" class="btn-add" onclick="openModal(\'pais\', \'pais-select-provincia\')">➕</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" onclick="closeModal(\'provincia\')">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            ';
        case 'canton':
            $provincias = $pdo->query("SELECT PR_ID, PR_NOMBRE FROM provincia WHERE PR_ESTADO = 'A' ORDER BY PR_NOMBRE")->fetchAll(PDO::FETCH_ASSOC);
            $provincia_options = '';
            foreach ($provincias as $provincia) {
                $provincia_options .= "<option value=\"{$provincia['PR_ID']}\">" . htmlspecialchars($provincia['PR_NOMBRE']) . "</option>";
            }
            return '
                <form>
                    <div class="form-group">
                        <label>Nombre del Cantón *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Provincia *</label>
                        <div class="input-with-button">
                            <select name="provincia" id="provincia-select-canton" required>
                                ' . $provincia_options . '
                            </select>
                            <button type="button" class="btn-add" onclick="openModal(\'provincia\', \'provincia-select-canton\')">➕</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" onclick="closeModal(\'canton\')">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            ';
        case 'parroquia':
            $cantones = $pdo->query("SELECT CAN_ID, CAN_NOMBRE FROM canton WHERE CAN_ESTADO = 'A' ORDER BY CAN_NOMBRE")->fetchAll(PDO::FETCH_ASSOC);
            $options = '';
            foreach ($cantones as $canton) {
                $options .= "<option value=\"{$canton['CAN_ID']}\">" . htmlspecialchars($canton['CAN_NOMBRE']) . "</option>";
            }
            return '
                <form>
                    <div class="form-group">
                        <label>Nombre de la Parroquia *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Dirección *</label>
                        <input type="text" name="direccion" required>
                    </div>
                     <div class="form-group">
                        <label>Cantón *</label>
                        <div class="input-with-button">
                            <select name="canton" id="canton-select-parroquia" required>
                                ' . $options . '
                            </select>
                            <button type="button" class="btn-add" onclick="openModal(\'canton\', \'canton-select-parroquia\')">➕</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" onclick="closeModal(\'parroquia\')">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            ';
        default:
            return '<p>Formulario no encontrado.</p>';
    }
}

function createEntity($entity, $data, $pdo) {
    switch($entity) {
        case 'instruccion_formal':
            try {
                if (empty($data['titulo']) || empty($data['institucion']) || empty($data['nivel']) || empty($data['parroquia'])) {
                    return ['success' => false, 'message' => 'Por favor, complete todos los campos requeridos.'];
                }
                $stmt = $pdo->prepare("INSERT INTO instruccion_formal (INF_TITULO, INF_INSTITUCION, NV_ID, PRR_ID, INF_ESTADO) VALUES (?, ?, ?, ?, 'A')");
                $stmt->execute([$data['titulo'], $data['institucion'], $data['nivel'], $data['parroquia']]);
                $newId = $pdo->lastInsertId();
                return ['success' => true, 'id' => $newId, 'text' => $data['titulo'] . ' - ' . $data['institucion']];
            } catch (PDOException $e) {
                error_log("Error creating instruccion_formal: " . $e->getMessage());
                return ['success' => false, 'message' => 'Ocurrió un error al guardar los datos.'];
            }
        case 'pais':
            try {
                if (empty($data['nombre'])) {
                    return ['success' => false, 'message' => 'Por favor, complete todos los campos requeridos.'];
                }
                $stmt = $pdo->prepare("INSERT INTO pais (PS_NOMBRE, PS_ESTADO) VALUES (?, 'A')");
                $stmt->execute([$data['nombre']]);
                $newId = $pdo->lastInsertId();
                return ['success' => true, 'id' => $newId, 'text' => $data['nombre']];
            } catch (PDOException $e) {
                error_log("Error creating pais: " . $e->getMessage());
                return ['success' => false, 'message' => 'Ocurrió un error al guardar los datos.'];
            }
        case 'provincia':
            try {
                if (empty($data['nombre']) || empty($data['pais'])) {
                    return ['success' => false, 'message' => 'Por favor, complete todos los campos requeridos.'];
                }
                $stmt = $pdo->prepare("INSERT INTO provincia (PR_NOMBRE, PS_ID, PR_ESTADO) VALUES (?, ?, 'A')");
                $stmt->execute([$data['nombre'], $data['pais']]);
                $newId = $pdo->lastInsertId();
                return ['success' => true, 'id' => $newId, 'text' => $data['nombre']];
            } catch (PDOException $e) {
                error_log("Error creating provincia: " . $e->getMessage());
                return ['success' => false, 'message' => 'Ocurrió un error al guardar los datos.'];
            }
        case 'canton':
            try {
                if (empty($data['nombre']) || empty($data['provincia'])) {
                    return ['success' => false, 'message' => 'Por favor, complete todos los campos requeridos.'];
                }
                $stmt = $pdo->prepare("INSERT INTO canton (CAN_NOMBRE, PR_ID, CAN_ESTADO) VALUES (?, ?, 'A')");
                $stmt->execute([$data['nombre'], $data['provincia']]);
                $newId = $pdo->lastInsertId();
                return ['success' => true, 'id' => $newId, 'text' => $data['nombre']];
            } catch (PDOException $e) {
                error_log("Error creating canton: " . $e->getMessage());
                return ['success' => false, 'message' => 'Ocurrió un error al guardar los datos.'];
            }
        case 'parroquia':
            try {
                if (empty($data['nombre']) || empty($data['direccion']) || empty($data['canton'])) {
                     return ['success' => false, 'message' => 'Por favor, complete todos los campos requeridos.'];
                }
                $stmt = $pdo->prepare("INSERT INTO parroquia (PRR_NOMBRE, PRR_DIRECCION, CAN_ID, PRR_ESTADO) VALUES (?, ?, ?, 'A')");
                $stmt->execute([$data['nombre'], $data['direccion'], $data['canton']]);
                $newId = $pdo->lastInsertId();
                return ['success' => true, 'id' => $newId, 'text' => $data['nombre']];
            } catch (PDOException $e) {
                error_log("Error creating parroquia: " . $e->getMessage());
                return ['success' => false, 'message' => 'Ocurrió un error al guardar los datos.'];
            }
        default:
            return ['success' => false, 'message' => 'Invalid entity for creation.'];
    }
}
?>