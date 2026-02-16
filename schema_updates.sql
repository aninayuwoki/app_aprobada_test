-- ACTUALIZACIÓN DE ESQUEMA PARA SIS_CERTIFI_CPATEEC
-- Este script agrega las columnas faltantes y renombra las necesarias para la compatibilidad con la aplicación

USE sis_certifi_cpateec;

-- 1. Tabla: usuario
-- Agregar columnas adicionales para información detallada del usuario
ALTER TABLE usuario
ADD COLUMN US_GENERO VARCHAR(20) AFTER US_APELLIDO,
ADD COLUMN US_TELEFONO VARCHAR(20) AFTER US_CONTACTO,
ADD COLUMN US_CELULAR VARCHAR(20) AFTER US_TELEFONO,
ADD COLUMN US_ESTADO_CIVIL VARCHAR(50) AFTER US_CORREO,
ADD COLUMN US_EDAD INT AFTER US_ESTADO_CIVIL,
ADD COLUMN US_TIPO_SANGRE VARCHAR(5) AFTER US_EDAD,
ADD COLUMN US_ETNIA VARCHAR(50) AFTER US_TIPO_SANGRE;

-- 2. Tabla: presentacion_certificado_curso
-- Agregar token para enlaces públicos y código único visible
ALTER TABLE presentacion_certificado_curso
ADD COLUMN PCC_TOKEN VARCHAR(255) AFTER PCC_ESTADO,
ADD COLUMN PCC_CODIGO_UNICO VARCHAR(100) AFTER PCC_TOKEN;

-- 3. Tabla: presentacion_certificado_oec
-- Agregar token y campos de vigencia, renombrar número de certificado
ALTER TABLE presentacion_certificado_oec
ADD COLUMN POEC_TOKEN VARCHAR(255) AFTER POEC_ESTADO,
CHANGE COLUMN POEC_NUMERO_CERTIFICADO POEC_CODIGO_CERTIFICACION VARCHAR(100),
ADD COLUMN POEC_F_INICIO DATE AFTER POEC_CODIGO_CERTIFICACION,
ADD COLUMN POEC_F_FIN DATE AFTER POEC_F_INICIO;

-- 4. Tabla: registro_oec
-- Renombrar código para que coincida con el uso en formularios
ALTER TABLE registro_oec
CHANGE COLUMN RGO_CODIGO RGO_CODIGO_FORMULARIO INT(11);

-- 5. Tabla: horario_de_curso
-- Agregar descripción para facilitar la selección en el panel de administración
ALTER TABLE horario_de_curso
ADD COLUMN HC_DESCRIPCION VARCHAR(255) AFTER HC_ESTADO;

-- 6. Tabla: tipo_de_usuario
-- Renombrar nombre a descripción
ALTER TABLE tipo_de_usuario
CHANGE COLUMN TUS_NOMBRE TUS_DESCRIPCION VARCHAR(100);

-- 7. Tabla: instruccion_formal
-- Agregar título de la instrucción y renombrar institución
ALTER TABLE instruccion_formal
ADD COLUMN INF_TITULO VARCHAR(200) AFTER INF_ID,
CHANGE COLUMN INF_NOMBRE_INSTITUCION INF_INSTITUCION VARCHAR(100);
