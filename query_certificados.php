<?php
header('Content-Type: application/json');
include 'db.php';

$response = ['success' => false, 'message' => 'Cédula no proporcionada.'];

// Función para generar token único
function generarToken() {
    return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
}

if (isset($_POST['cedula'])) {
    $cedula = $_POST['cedula'];

    try {
        $stmt = $pdo->prepare("CALL consulta_certificados(:cedula)");
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($resultados) {
            // Generar tokens para certificados que no lo tengan
            foreach ($resultados as &$cert) {
                if ($cert['tipo_certificado'] === 'CURSO') {
                    // Verificar si ya tiene token
                    $stmtCheck = $pdo->prepare("SELECT PCC_TOKEN FROM presentacion_certificado_curso WHERE PCC_CODIGO_UNICO = ?");
                    $stmtCheck->execute([$cert['codigo_certificado']]);
                    $tokenData = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    
                    if (empty($tokenData['PCC_TOKEN'])) {
                        // Generar y guardar nuevo token
                        $nuevoToken = generarToken();
                        $stmtUpdate = $pdo->prepare("UPDATE presentacion_certificado_curso SET PCC_TOKEN = ? WHERE PCC_CODIGO_UNICO = ?");
                        $stmtUpdate->execute([$nuevoToken, $cert['codigo_certificado']]);
                        $cert['token'] = $nuevoToken;
                    } else {
                        $cert['token'] = $tokenData['PCC_TOKEN'];
                    }
                } elseif ($cert['tipo_certificado'] === 'OEC') {
                    // Para OEC
                    $stmtCheck = $pdo->prepare("SELECT POEC_TOKEN FROM presentacion_certificado_oec WHERE POEC_CODIGO_CERTIFICACION = ?");
                    $stmtCheck->execute([$cert['codigo_certificado']]);
                    $tokenData = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    
                    if (empty($tokenData['POEC_TOKEN'])) {
                        $nuevoToken = generarToken();
                        $stmtUpdate = $pdo->prepare("UPDATE presentacion_certificado_oec SET POEC_TOKEN = ? WHERE POEC_CODIGO_CERTIFICACION = ?");
                        $stmtUpdate->execute([$nuevoToken, $cert['codigo_certificado']]);
                        $cert['token'] = $nuevoToken;
                    } else {
                        $cert['token'] = $tokenData['POEC_TOKEN'];
                    }
                }
            }
            
            $response = [
                'success' => true,
                'user' => [
                    'cedula' => $resultados[0]['cedula'],
                    'nombre_completo' => $resultados[0]['nombre_completo'],
                    'nombres' => $resultados[0]['nombres'],
                    'apellidos' => $resultados[0]['apellidos']
                ],
                'certificados' => $resultados
            ];
        } else {
            $response = ['success' => false, 'message' => 'No se encontraron certificados para la cédula ' . htmlspecialchars($cedula)];
        }
    } catch (PDOException $e) {
        error_log('Database query failed: ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Ocurrió un error al procesar su solicitud.'];
    }
}

echo json_encode($response);
?>