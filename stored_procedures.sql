-- PROCEDIMIENTOS ALMACENADOS PARA SIS_CERTIFI_CPATEEC
-- Estos procedimientos manejan las consultas principales de la aplicación web

USE sis_certifi_cpateec;

DELIMITER //

-- 1. Procedimiento para consultar matrículas de un usuario
DROP PROCEDURE IF EXISTS consulta_matriculas //
CREATE PROCEDURE consulta_matriculas(IN p_cedula VARCHAR(10))
BEGIN
    SELECT
        u.US_CEDULA AS cedula,
        u.US_NOMBRE AS us_nombre,
        u.US_APELLIDO AS us_apellido,
        u.US_GENERO AS us_genero,
        IF(m.MAT_ID IS NOT NULL, 1, 0) AS tiene_matricula,
        m.MAT_CODIGO AS mat_codigo,
        c.CUR_NOMBRE AS cur_nombre,
        e.EMP_NOMBRE AS emp_nombre,
        dh.DH_HORA_INICIO AS dh_hora_inicio,
        dh.DH_HORA_FIN AS dh_hora_fin,
        c.CUR_HORA_TOTAL AS cur_hora_total
    FROM usuario u
    LEFT JOIN matricula m ON u.US_ID = m.US_ID AND m.MAT_ESTADO = 'A'
    LEFT JOIN curso c ON m.CUR_ID = c.CUR_ID
    LEFT JOIN empresa e ON m.EMP_ID = e.EMP_ID
    LEFT JOIN horario_de_curso hc ON m.HC_ID = hc.HC_ID
    LEFT JOIN detalle_horario dh ON hc.HC_ID = dh.HC_ID AND dh.DH_ESTADO = 'A'
    WHERE u.US_CEDULA = p_cedula AND u.US_ESTADO = 'A';
END //

-- 2. Procedimiento para consultar certificados (Cursos y OEC) de un usuario
DROP PROCEDURE IF EXISTS consulta_certificados //
CREATE PROCEDURE consulta_certificados(IN p_cedula VARCHAR(10))
BEGIN
    -- Certificados de Cursos
    SELECT
        u.US_CEDULA AS cedula,
        CONCAT(u.US_NOMBRE, ' ', u.US_APELLIDO) AS nombre_completo,
        u.US_NOMBRE AS nombres,
        u.US_APELLIDO AS apellidos,
        'CURSO' AS tipo_certificado,
        c.CUR_NOMBRE AS nombre_certificacion,
        pcc.PCC_CODIGO_UNICO AS codigo_certificado,
        'CPATEEC' AS empresa_capacitadora,
        m.MAT_FECHA_INICIAL AS fecha_inicio_vigencia,
        m.MAT_FECHA_FINAL AS fecha_fin_vigencia,
        c.CUR_HORA_TOTAL AS horas_totales,
        pcc.PCC_TOKEN AS token
    FROM usuario u
    INNER JOIN matricula m ON u.US_ID = m.US_ID
    INNER JOIN curso c ON m.CUR_ID = c.CUR_ID
    INNER JOIN presentacion_certificado_curso pcc ON m.MAT_ID = pcc.MAT_ID
    WHERE u.US_CEDULA = p_cedula AND pcc.PCC_ESTADO = 'A'

    UNION ALL

    -- Certificados OEC
    SELECT
        u.US_CEDULA AS cedula,
        CONCAT(u.US_NOMBRE, ' ', u.US_APELLIDO) AS nombre_completo,
        u.US_NOMBRE AS nombres,
        u.US_APELLIDO AS apellidos,
        'OEC' AS tipo_certificado,
        co.CON_NOMBRE AS nombre_certificacion,
        poec.POEC_CODIGO_CERTIFICACION AS codigo_certificado,
        e.EMP_NOMBRE AS empresa_capacitadora,
        poec.POEC_F_INICIO AS fecha_inicio_vigencia,
        poec.POEC_F_FIN AS fecha_fin_vigencia,
        NULL AS horas_totales,
        poec.POEC_TOKEN AS token
    FROM usuario u
    INNER JOIN registro_oec rgo ON u.US_ID = rgo.US_ID
    INNER JOIN certificaciones_oec co ON rgo.CON_ID = co.CON_ID
    INNER JOIN presentacion_certificado_oec poec ON rgo.RGO_ID = poec.RGO_ID
    INNER JOIN empresa e ON poec.EMP_ID = e.EMP_ID
    WHERE u.US_CEDULA = p_cedula AND poec.POEC_ESTADO = 'A';
END //

DELIMITER ;
