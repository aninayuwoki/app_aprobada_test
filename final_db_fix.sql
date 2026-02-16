-- ==========================================================
-- SCRIPT DEFINITIVO DE PROCEDIMIENTOS ALMACENADOS (V2)
-- Sistema de Certificaciones CPATEEC
-- ==========================================================
-- Este script crea los procedimientos necesarios asegurando
-- la compatibilidad total de tipos de datos entre consultas.

USE sis_certifi_cpateec;

DELIMITER //

-- 1. Procedimiento para consultar matrículas de un usuario
DROP PROCEDURE IF EXISTS consulta_matriculas //
CREATE PROCEDURE consulta_matriculas(IN p_cedula VARCHAR(10))
BEGIN
    SELECT
        CAST(u.US_CEDULA AS CHAR(10)) AS cedula,
        CAST(u.US_NOMBRE AS CHAR(50)) AS us_nombre,
        CAST(u.US_APELLIDO AS CHAR(50)) AS us_apellido,
        CAST(u.US_GENERO AS CHAR(20)) AS us_genero,
        CAST(IF(m.MAT_ID IS NOT NULL, 1, 0) AS UNSIGNED) AS tiene_matricula,
        CAST(m.MAT_CODIGO AS CHAR(20)) AS mat_codigo,
        CAST(c.CUR_NOMBRE AS CHAR(200)) AS cur_nombre,
        CAST(e.EMP_NOMBRE AS CHAR(200)) AS emp_nombre,
        dh.DH_HORA_INICIO AS dh_hora_inicio,
        dh.DH_HORA_FIN AS dh_hora_fin,
        CAST(c.CUR_HORA_TOTAL AS UNSIGNED) AS cur_hora_total
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
    -- Parte A: Certificados de Cursos
    SELECT
        CAST(u.US_CEDULA AS CHAR(10)) AS cedula,
        CAST(CONCAT(u.US_NOMBRE, ' ', u.US_APELLIDO) AS CHAR(150)) AS nombre_completo,
        CAST(u.US_NOMBRE AS CHAR(50)) AS nombres,
        CAST(u.US_APELLIDO AS CHAR(50)) AS apellidos,
        CAST('CURSO' AS CHAR(10)) AS tipo_certificado,
        CAST(c.CUR_NOMBRE AS CHAR(200)) AS nombre_certificacion,
        CAST(pcc.PCC_CODIGO_UNICO AS CHAR(100)) AS codigo_certificado,
        CAST(e.EMP_NOMBRE AS CHAR(200)) AS empresa_capacitadora,
        m.MAT_FECHA_INICIAL AS fecha_inicio_vigencia,
        m.MAT_FECHA_FINAL AS fecha_fin_vigencia,
        CAST(c.CUR_HORA_TOTAL AS UNSIGNED) AS horas_totales,
        CAST(pcc.PCC_TOKEN AS CHAR(255)) AS token
    FROM usuario u
    INNER JOIN matricula m ON u.US_ID = m.US_ID
    INNER JOIN curso c ON m.CUR_ID = c.CUR_ID
    INNER JOIN empresa e ON m.EMP_ID = e.EMP_ID
    INNER JOIN presentacion_certificado_curso pcc ON m.MAT_ID = pcc.MAT_ID
    WHERE u.US_CEDULA = p_cedula AND pcc.PCC_ESTADO = 'A' AND u.US_ESTADO = 'A'

    UNION ALL

    -- Parte B: Certificados OEC
    SELECT
        CAST(u.US_CEDULA AS CHAR(10)) AS cedula,
        CAST(CONCAT(u.US_NOMBRE, ' ', u.US_APELLIDO) AS CHAR(150)) AS nombre_completo,
        CAST(u.US_NOMBRE AS CHAR(50)) AS nombres,
        CAST(u.US_APELLIDO AS CHAR(50)) AS apellidos,
        CAST('OEC' AS CHAR(10)) AS tipo_certificado,
        CAST(co.CON_NOMBRE AS CHAR(200)) AS nombre_certificacion,
        CAST(poec.POEC_CODIGO_CERTIFICACION AS CHAR(100)) AS codigo_certificado,
        CAST(e.EMP_NOMBRE AS CHAR(200)) AS empresa_capacitadora,
        poec.POEC_F_INICIO AS fecha_inicio_vigencia,
        poec.POEC_F_FIN AS fecha_fin_vigencia,
        NULL AS horas_totales,
        CAST(poec.POEC_TOKEN AS CHAR(255)) AS token
    FROM usuario u
    INNER JOIN registro_oec rgo ON u.US_ID = rgo.US_ID
    INNER JOIN certificaciones_oec co ON rgo.CON_ID = co.CON_ID
    INNER JOIN presentacion_certificado_oec poec ON rgo.RGO_ID = poec.RGO_ID
    INNER JOIN empresa e ON poec.EMP_ID = e.EMP_ID
    WHERE u.US_CEDULA = p_cedula AND poec.POEC_ESTADO = 'A' AND u.US_ESTADO = 'A';
END //

DELIMITER ;
