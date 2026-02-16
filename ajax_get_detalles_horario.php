<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (isset($_GET['id'])) {
    $horarioId = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
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
        $stmt->execute([$horarioId]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'detalles' => $detalles]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
}
?>