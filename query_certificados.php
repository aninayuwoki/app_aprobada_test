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

        // Importante: consumir todos los result sets de la llamada al procedimiento
        // para evitar errores de "unbuffered queries" en consultas posteriores.
        $stmt->closeCursor();
        try { while($stmt->nextRowset()); } catch (Exception $e) {}

        if ($resultados) {
            // Generar tokens para certificados que no lo tengan
            foreach ($resultados as &$cert) {
                // Si el procedimiento ya devolvió un token, lo usamos
                if (!empty($cert['token'])) {
                    continue;
                }

                // Si no hay token, lo generamos y actualizamos la base de datos
                $nuevoToken = generarToken();
                if ($cert['tipo_certificado'] === 'CURSO') {
                    $stmtUpdate = $pdo->prepare("UPDATE presentacion_certificado_curso SET PCC_TOKEN = ? WHERE PCC_CODIGO_UNICO = ?");
                    $stmtUpdate->execute([$nuevoToken, $cert['codigo_certificado']]);
                    $cert['token'] = $nuevoToken;
                } elseif ($cert['tipo_certificado'] === 'OEC') {
                    $stmtUpdate = $pdo->prepare("UPDATE presentacion_certificado_oec SET POEC_TOKEN = ? WHERE POEC_CODIGO_CERTIFICACION = ?");
                    $stmtUpdate->execute([$nuevoToken, $cert['codigo_certificado']]);
                    $cert['token'] = $nuevoToken;
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
        // Enviar un poco más de info si es necesario para depuración,
        // pero por ahora mantenemos el mensaje genérico para seguridad.
        $response = ['success' => false, 'message' => 'Ocurrió un error al procesar su solicitud. Detalle técnico: ' . $e->getCode()];
    }
}

echo json_encode($response);
?>