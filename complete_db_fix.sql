-- ==========================================================
-- SCRIPT COMPLETO DE ACTUALIZACIÓN DE BASE DE DATOS
-- Sistema de Certificaciones CPATEEC
-- ==========================================================
-- Este script realiza dos tareas principales:
-- 1. Actualiza el esquema de las tablas para que coincida con la aplicación.
-- 2. Crea los procedimientos almacenados necesarios para las consultas.

-- INSTRUCCIONES:
-- 1. Abra su cliente de MySQL (phpMyAdmin, MySQL Workbench, o línea de comandos).
-- 2. Asegúrese de estar conectado a la base de datos 'sis_certifi_cpateec'.
-- 3. Copie y pegue todo el contenido de este script y ejecútelo.

-- ==========================================================
-- PARTE 1: ACTUALIZACIÓN DE ESQUEMA (TABLAS Y COLUMNAS)
-- ==========================================================

USE sis_certifi_cpateec;

-- Tabla: usuario
-- Agregamos campos que la aplicación usa en el panel de administración
ALTER TABLE usuario
ADD COLUMN US_GENERO VARCHAR(20) AFTER US_APELLIDO,
ADD COLUMN US_TELEFONO VARCHAR(20) AFTER US_CONTACTO,
ADD COLUMN US_CELULAR VARCHAR(20) AFTER US_TELEFONO,
ADD COLUMN US_ESTADO_CIVIL VARCHAR(50) AFTER US_CORREO,
ADD COLUMN US_EDAD INT AFTER US_ESTADO_CIVIL,
ADD COLUMN US_TIPO_SANGRE VARCHAR(5) AFTER US_EDAD,
ADD COLUMN US_ETNIA VARCHAR(50) AFTER US_TIPO_SANGRE;

-- Tabla: presentacion_certificado_curso
-- Campos para seguridad (token) y identificación única del certificado
ALTER TABLE presentacion_certificado_curso
ADD COLUMN PCC_TOKEN VARCHAR(255) AFTER PCC_ESTADO,
ADD COLUMN PCC_CODIGO_UNICO VARCHAR(100) AFTER PCC_TOKEN;

-- Tabla: presentacion_certificado_oec
-- Campos para seguridad (token) y identificación única del certificado
ALTER TABLE presentacion_certificado_oec
ADD COLUMN POEC_TOKEN VARCHAR(255) AFTER POEC_ESTADO,
CHANGE COLUMN POEC_NUMERO_CERTIFICADO POEC_CODIGO_CERTIFICACION VARCHAR(100),
ADD COLUMN POEC_F_INICIO DATE AFTER POEC_CODIGO_CERTIFICACION,
ADD COLUMN POEC_F_FIN DATE AFTER POEC_F_INICIO;

-- Tabla: registro_oec
-- Estandarización de nombres de columnas
ALTER TABLE registro_oec
CHANGE COLUMN RGO_CODIGO RGO_CODIGO_FORMULARIO INT(11);

-- Tabla: horario_de_curso
-- Campo descriptivo para el panel de administración
ALTER TABLE horario_de_curso
ADD COLUMN HC_DESCRIPCION VARCHAR(255) AFTER HC_ESTADO;

-- Tabla: tipo_de_usuario
-- Estandarización de nombres de columnas
ALTER TABLE tipo_de_usuario
CHANGE COLUMN TUS_NOMBRE TUS_DESCRIPCION VARCHAR(100);

-- Tabla: instruccion_formal
-- Estandarización y ampliación de campos
ALTER TABLE instruccion_formal
ADD COLUMN INF_TITULO VARCHAR(200) AFTER INF_ID,
CHANGE COLUMN INF_NOMBRE_INSTITUCION INF_INSTITUCION VARCHAR(100);


-- ==========================================================
-- PARTE 2: PROCEDIMIENTOS ALMACENADOS
-- ==========================================================

DELIMITER //

-- Procedimiento: consulta_matriculas
-- Usado en la página principal para buscar matrículas activas por cédula
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

-- Procedimiento: consulta_certificados
-- Usado en la sección de certificados para obtener tanto de cursos como OEC
DROP PROCEDURE IF EXISTS consulta_certificados //
CREATE PROCEDURE consulta_certificados(IN p_cedula VARCHAR(10))
BEGIN
    -- Parte A: Certificados de Cursos
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

    -- Parte B: Certificados OEC
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

-- ==========================================================
-- FIN DEL SCRIPT
-- ==========================================================
