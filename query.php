<?php
header('Content-Type: application/json');
include 'db.php';

$response = ['success' => false, 'message' => 'Cédula no proporcionada.'];

if (isset($_POST['cedula'])) {
    $cedula = $_POST['cedula'];

    try {
        $stmt = $pdo->prepare("CALL consulta_matriculas(:cedula)");
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($resultados && $resultados[0]['cedula'] !== null) {
            // Verificar si el usuario tiene matrículas
            $tieneMatricula = false;
            foreach ($resultados as $resultado) {
                if ($resultado['tiene_matricula'] == 1) {
                    $tieneMatricula = true;
                    break;
                }
            }
            
            // Si el usuario existe pero no tiene matrículas, mostrar solo un registro
            if (!$tieneMatricula) {
                $response = [
                    'success' => true,
                    'user' => [
                        'us_cedula' => $resultados[0]['cedula'],
                        'us_nombre' => $resultados[0]['us_nombre'],
                        'us_apellido' => $resultados[0]['us_apellido'],
                        'us_genero' => $resultados[0]['us_genero']
                    ],
                    'courses' => [],
                    'message' => 'Usuario encontrado pero sin matrículas activas'
                ];
            } else {
                // El usuario tiene matrículas
                $courses = [];
                foreach ($resultados as $resultado) {
                    if ($resultado['tiene_matricula'] == 1) {
                        $courses[] = [
                            'mat_codigo' => $resultado['mat_codigo'],
                            'cur_nombre' => $resultado['cur_nombre'],
                            'emp_nombre' => $resultado['emp_nombre'],
                            'dh_hora_inicio' => $resultado['dh_hora_inicio'],
                            'dh_hora_fin' => $resultado['dh_hora_fin'],
                            'cur_hora_total' => $resultado['cur_hora_total']
                        ];
                    }
                }
                
                $response = [
                    'success' => true,
                    'user' => [
                        'us_cedula' => $resultados[0]['cedula'],
                        'us_nombre' => $resultados[0]['us_nombre'],
                        'us_apellido' => $resultados[0]['us_apellido'],
                        'us_genero' => $resultados[0]['us_genero']
                    ],
                    'courses' => $courses
                ];
            }
        } else {
            $response = [
                'success' => false, 
                'message' => 'No se encontró ningún usuario con la cédula ' . htmlspecialchars($cedula)
            ];
        }
    } catch (PDOException $e) {
        error_log('Database query failed: ' . $e->getMessage());
        $response = [
            'success' => false, 
            'message' => 'Ocurrió un error al procesar su solicitud.'
        ];
    }
}

echo json_encode($response);
?>