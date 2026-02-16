<?php
include 'db.php';

$error = null;
$certificado = null;
$usuario = null;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Buscar en certificados de curso
        $stmt = $pdo->prepare("
            SELECT 
                'CURSO' AS tipo_certificado,
                u.US_CEDULA AS cedula,
                CONCAT(u.US_NOMBRE, ' ', u.US_APELLIDO) AS nombre_completo,
                u.US_NOMBRE AS nombres,
                u.US_APELLIDO AS apellidos,
                cur.CUR_NOMBRE AS nombre_certificacion,
                emp.EMP_NOMBRE AS empresa_capacitadora,
                mat.MAT_FECHA_INICIAL AS fecha_inicio_vigencia,
                mat.MAT_FECHA_FINAL AS fecha_fin_vigencia,
                pcc.PCC_CODIGO_UNICO AS codigo_certificado,
                cur.CUR_HORA_TOTAL AS horas_totales,
                pcc.PCC_TOKEN AS token
            FROM presentacion_certificado_curso pcc
            INNER JOIN matricula mat ON pcc.MAT_ID = mat.MAT_ID
            INNER JOIN usuario u ON mat.US_ID = u.US_ID
            INNER JOIN curso cur ON mat.CUR_ID = cur.CUR_ID
            INNER JOIN empresa emp ON mat.EMP_ID = emp.EMP_ID
            WHERE pcc.PCC_TOKEN = ?
        ");
        $stmt->execute([$token]);
        $certificado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no se encuentra, buscar en OEC
        if (!$certificado) {
            $stmt = $pdo->prepare("
                SELECT 
                    'OEC' AS tipo_certificado,
                    u.US_CEDULA AS cedula,
                    CONCAT(u.US_NOMBRE, ' ', u.US_APELLIDO) AS nombre_completo,
                    u.US_NOMBRE AS nombres,
                    u.US_APELLIDO AS apellidos,
                    con.CON_NOMBRE AS nombre_certificacion,
                    emp.EMP_NOMBRE AS empresa_capacitadora,
                    pco.POEC_F_INICIO AS fecha_inicio_vigencia,
                    pco.POEC_F_FIN AS fecha_fin_vigencia,
                    pco.POEC_CODIGO_CERTIFICACION AS codigo_certificado,
                    NULL AS horas_totales,
                    pco.POEC_TOKEN AS token
                FROM presentacion_certificado_oec pco
                INNER JOIN registro_oec rgo ON pco.RGO_ID = rgo.RGO_ID
                INNER JOIN usuario u ON rgo.US_ID = u.US_ID
                INNER JOIN certificaciones_oec con ON rgo.CON_ID = con.CON_ID
                INNER JOIN empresa emp ON pco.EMP_ID = emp.EMP_ID
                WHERE pco.POEC_TOKEN = ?
            ");
            $stmt->execute([$token]);
            $certificado = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$certificado) {
            $error = "Token inválido o certificado no encontrado.";
        }
    } catch (PDOException $e) {
        error_log('Database query failed: ' . $e->getMessage());
        $error = "Ocurrió un error al buscar el certificado.";
    }
} else {
    $error = "Token no proporcionado.";
}

function formatearFecha($fecha) {
    if (!$fecha) return 'N/A';
    $timestamp = strtotime($fecha);
    return date('d \d\e F \d\e Y', $timestamp);
}
?>
<?php include 'partials/header.php'; ?>

<div class="container">
    <?php if ($error): ?>
        <div class="error-message">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="certificados.php" class="btn">Ir a búsqueda de certificados</a>
        </div>
    <?php else: ?>
        <div class="certificado-publico">
            <div class="certificado-header-publico">
                <h1>Certificado Verificado</h1>
                <span class="tipo-badge <?php echo strtolower($certificado['tipo_certificado']); ?>">
                    <?php echo htmlspecialchars($certificado['tipo_certificado']); ?>
                </span>
            </div>
            
            <div class="certificado-contenido">
                <section class="seccion-usuario">
                    <h2>Datos del Titular</h2>
                    <table class="tabla-datos">
                        <tr>
                            <th>Nombre Completo:</th>
                            <td><?php echo htmlspecialchars($certificado['nombre_completo']); ?></td>
                        </tr>
                        <tr>
                            <th>Cédula:</th>
                            <td><?php echo htmlspecialchars($certificado['cedula']); ?></td>
                        </tr>
                    </table>
                </section>
                
                <section class="seccion-certificado">
                    <h2>Información del Certificado</h2>
                    <table class="tabla-datos">
                        <tr>
                            <th>Certificación:</th>
                            <td><?php echo htmlspecialchars($certificado['nombre_certificacion']); ?></td>
                        </tr>
                        <tr>
                            <th>Código:</th>
                            <td><strong><?php echo htmlspecialchars($certificado['codigo_certificado']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Empresa Capacitadora:</th>
                            <td><?php echo htmlspecialchars($certificado['empresa_capacitadora']); ?></td>
                        </tr>
                        <tr>
                            <th>Vigencia:</th>
                            <td>
                                Desde: <?php echo formatearFecha($certificado['fecha_inicio_vigencia']); ?><br>
                                Hasta: <?php echo formatearFecha($certificado['fecha_fin_vigencia']); ?>
                            </td>
                        </tr>
                        <?php if ($certificado['horas_totales']): ?>
                        <tr>
                            <th>Horas Totales:</th>
                            <td><?php echo htmlspecialchars($certificado['horas_totales']); ?> horas</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </section>
                
                <div class="certificado-footer">
                    <p class="texto-verificacion">
                        ✓ Este certificado ha sido verificado y es válido.
                    </p>
                    <button onclick="window.print()" class="btn btn-imprimir">Imprimir Certificado</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>