<?php
/* For license terms, see /license.txt */
/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php
 * @package chamilo.plugin.sepe
 */
/**
 * Check if script can be called
 */
if (!function_exists('api_get_path')) {
    die('This script must be loaded through the Chamilo plugin installer sequence');
}

$entityManager = Database::getManager();
$pluginSchema = new \Doctrine\DBAL\Schema\Schema();
$connection = $entityManager->getConnection();
$platform = $connection->getDatabasePlatform();

//Create tables
/* ==========    PLUGIN_SEPE_CENTER    ========== */
$sepeCenterTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_CENTER);
$sepeCenterTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeCenterTable->addColumn('origen_centro', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('codigo_centro', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('nombre_centro', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('url', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('url_seguimiento', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('telefono', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('email', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->setPrimaryKey(array('cod'));

/* ==========    PLUGIN_SEPE_ACTIONS    ========== */
$sepeActionsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_ACTIONS);
$sepeActionsTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeActionsTable->addColumn(
    'ORIGEN_ACCION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeActionsTable->addColumn(
    'CODIGO_ACCION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 30)
); 
$sepeActionsTable->addColumn(
    'SITUACION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeActionsTable->addColumn(
    'ORIGEN_ESPECIALIDAD',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeActionsTable->addColumn(
    'AREA_PROFESIONAL',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 4)
);
$sepeActionsTable->addColumn(
    'CODIGO_ESPECIALIDAD',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 14)
);
$sepeActionsTable->addColumn(
    'DURACION',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeActionsTable->addColumn('FECHA_INICIO', \Doctrine\DBAL\Types\Type::DATE);
$sepeActionsTable->addColumn('FECHA_FIN', \Doctrine\DBAL\Types\Type::DATE);
$sepeActionsTable->addColumn(
    'IND_ITINERARIO_COMPLETO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
); //enum('SI','NO')
$sepeActionsTable->addColumn(
    'TIPO_FINANCIACION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
); 
$sepeActionsTable->addColumn(
    'NUMERO_ASISTENTES',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);     
$sepeActionsTable->addColumn(
    'DENOMINACION_ACCION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 250)
);
$sepeActionsTable->addColumn('INFORMACION_GENERAL', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->addColumn('HORARIOS', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->addColumn('REQUISITOS', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->addColumn('CONTACTO_ACCION', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->setPrimaryKey(array('cod'));

/* ==========    PLUGIN_SEPE_SPECIALTY    ========== */
$sepeSpecialtyTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_SPECIALTY);
$sepeSpecialtyTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeSpecialtyTable->addColumn(
    'cod_action',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTable->addColumn(
    'ORIGEN_ESPECIALIDAD',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeSpecialtyTable->addColumn(
    'AREA_PROFESIONAL',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 4)
); 
$sepeSpecialtyTable->addColumn(
    'CODIGO_ESPECIALIDAD',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 14)
);
$sepeSpecialtyTable->addColumn(
    'ORIGEN_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeSpecialtyTable->addColumn(
    'CODIGO_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 16)
);
$sepeSpecialtyTable->addColumn('FECHA_INICIO', \Doctrine\DBAL\Types\Type::DATE);
$sepeSpecialtyTable->addColumn('FECHA_FIN', \Doctrine\DBAL\Types\Type::DATE);
$sepeSpecialtyTable->addColumn(
    'MODALIDAD_IMPARTICION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeSpecialtyTable->addColumn(
    'HORAS_PRESENCIAL',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTable->addColumn(
    'HORAS_TELEFORMACION',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTable->addColumn(
    'HM_NUM_PARTICIPANTES',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HM_NUMERO_ACCESOS',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HM_DURACION_TOTAL',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HT_NUM_PARTICIPANTES',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HT_NUMERO_ACCESOS',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HT_DURACION_TOTAL',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HN_NUM_PARTICIPANTES',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HN_NUMERO_ACCESOS',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'HN_DURACION_TOTAL',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'NUM_PARTICIPANTES',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'NUMERO_ACTIVIDADES_APRENDIZAJE',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'NUMERO_INTENTOS',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->addColumn(
    'NUMERO_ACTIVIDADES_EVALUACION',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true, 'notnull' => false)
);
$sepeSpecialtyTable->setPrimaryKey(array('cod'));
$sepeSpecialtyTable->addForeignKeyConstraint(
    $sepeActionsTable,
    array('cod_action'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);

/* ==========    PLUGIN_SEPE_CENTROS        ========== */
$sepeCentrosTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_CENTROS);
$sepeCentrosTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeCentrosTable->addColumn(
    'ORIGEN_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeCentrosTable->addColumn(
    'CODIGO_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 16)
);
$sepeCentrosTable->setPrimaryKey(array('cod'));

/* ==========    PLUGIN_SEPE_SPECIALTY_CLASSROOM        ========== */
$sepeSpecialtyClassroomTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_SPECIALTY_CLASSROOM);
$sepeSpecialtyClassroomTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeSpecialtyClassroomTable->addColumn(
    'cod_specialty',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyClassroomTable->addColumn(
    'cod_centro',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyClassroomTable->setPrimaryKey(array('cod'));
$sepeSpecialtyClassroomTable->addForeignKeyConstraint(
    $sepeSpecialtyTable,
    array('cod_specialty'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);

/* ==========    PLUGIN_SEPE_TUTORS       ========== */        
$sepeTutorsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_TUTORS);
$sepeTutorsTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeTutorsTable->addColumn(
    'cod_user_chamilo',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeTutorsTable->addColumn(
    'TIPO_DOCUMENTO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1)
); //enum('D','E','U','W','G','H')
$sepeTutorsTable->addColumn(
    'NUM_DOCUMENTO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 10)
);
$sepeTutorsTable->addColumn(
    'LETRA_NIF',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1)
);
$sepeTutorsTable->addColumn(
    'ACREDITACION_TUTOR',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 200)
);
$sepeTutorsTable->addColumn(
    'EXPERIENCIA_PROFESIONAL',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeTutorsTable->addColumn(
    'COMPETENCIA_DOCENTE',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeTutorsTable->addColumn(
    'EXPERIENCIA_MODALIDAD_TELEFORMACION',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeTutorsTable->addColumn(
    'FORMACION_MODALIDAD_TELEFORMACION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeTutorsTable->setPrimaryKey(array('cod'));
        
/* ==========    PLUGIN_SEPE_SPECIALTY_TUTORS    ========== */ 
$sepeSpecialtyTutorsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_SPECIALTY_TUTORS);
$sepeSpecialtyTutorsTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeSpecialtyTutorsTable->addColumn(
    'cod_specialty',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTutorsTable->addColumn(
    'cod_tutor',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTutorsTable->addColumn(
    'ACREDITACION_TUTOR',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 200)
);
$sepeSpecialtyTutorsTable->addColumn(
    'EXPERIENCIA_PROFESIONAL',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTutorsTable->addColumn(
    'COMPETENCIA_DOCENTE',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeSpecialtyTutorsTable->addColumn(
    'EXPERIENCIA_MODALIDAD_TELEFORMACION',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeSpecialtyTutorsTable->addColumn(
    'FORMACION_MODALIDAD_TELEFORMACION',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeSpecialtyTutorsTable->setPrimaryKey(array('cod'));
$sepeSpecialtyTutorsTable->addForeignKeyConstraint(
    $sepeSpecialtyTable,
    array('cod_specialty'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);      

/* ==========    PLUGIN_SEPE_TUTORS_EMPRESA   ========== */
$sepeTutorsEmpresaTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_TUTORS_EMPRESA);
$sepeTutorsEmpresaTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeTutorsEmpresaTable->addColumn(
    'alias',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 255)
);
$sepeTutorsEmpresaTable->addColumn(
    'TIPO_DOCUMENTO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1, 'notnull' => false)
); //enum('D','E','U','W','G','H')
$sepeTutorsEmpresaTable->addColumn(
    'NUM_DOCUMENTO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 10, 'notnull' => false)
);
$sepeTutorsEmpresaTable->addColumn(
    'LETRA_NIF',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1, 'notnull' => false)
);
$sepeTutorsEmpresaTable->addColumn(
    'empresa',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeTutorsEmpresaTable->addColumn(
    'formacion',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);    
$sepeTutorsEmpresaTable->setPrimaryKey(array('cod'));    
    
/* ==========    PLUGIN_SEPE_PARTICIPANTS    ========== */ 
$sepeParticipantsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_PARTICIPANTS);
$sepeParticipantsTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeParticipantsTable->addColumn(
    'cod_action',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeParticipantsTable->addColumn(
    'cod_user_chamilo',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeParticipantsTable->addColumn(
    'TIPO_DOCUMENTO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1)
); //enum('D','E','U','W','G','H')
$sepeParticipantsTable->addColumn(
    'NUM_DOCUMENTO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 10)
);
$sepeParticipantsTable->addColumn(
    'LETRA_NIF',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1)
);
$sepeParticipantsTable->addColumn(
    'INDICADOR_COMPETENCIAS_CLAVE',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeParticipantsTable->addColumn(
    'ID_CONTRATO_CFA',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 14, 'notnull' => false)
);
$sepeParticipantsTable->addColumn(
    'CIF_EMPRESA',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 9, 'notnull' => false)
);
$sepeParticipantsTable->addColumn(
    'cod_tutor_empresa',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeParticipantsTable->addColumn(
    'cod_tutor_formacion',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeParticipantsTable->setPrimaryKey(array('cod'));
$sepeParticipantsTable->addForeignKeyConstraint(
    $sepeActionsTable,
    array('cod_action'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);  
$sepeParticipantsTable->addForeignKeyConstraint(
    $sepeTutorsEmpresaTable,
    array('cod_tutor_empresa'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);     
$sepeParticipantsTable->addForeignKeyConstraint(
    $sepeTutorsEmpresaTable,
    array('cod_tutor_formacion'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);  

/* ==========    PLUGIN_SEPE_PARTICIPANTS_SPECIALTY    ========== */ 
$sepeParticipantsSpecialtyTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY);
$sepeParticipantsSpecialtyTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'cod_participant',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'ORIGEN_ESPECIALIDAD',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'AREA_PROFESIONAL',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 4, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'CODIGO_ESPECIALIDAD',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 14, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'FECHA_ALTA', 
    \Doctrine\DBAL\Types\Type::DATE,
    array('notnull' => false)    
);
$sepeParticipantsSpecialtyTable->addColumn(
    'FECHA_BAJA', 
    \Doctrine\DBAL\Types\Type::DATE,
    array('notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'ORIGEN_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'CODIGO_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 16, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'FECHA_INICIO', 
    \Doctrine\DBAL\Types\Type::DATE,
    array('notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'FECHA_FIN', 
    \Doctrine\DBAL\Types\Type::DATE,
    array('notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'RESULTADO_FINAL',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 1, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'CALIFICACION_FINAL',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 4, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->addColumn(
    'PUNTUACION_FINAL',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 4, 'notnull' => false)
);
$sepeParticipantsSpecialtyTable->setPrimaryKey(array('cod'));
$sepeParticipantsSpecialtyTable->addForeignKeyConstraint(
    $sepeParticipantsTable,
    array('cod_participant'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);       

/* ==========    PLUGIN_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS   ========== */ 
$sepeParticipantsSpecialtyTutorialsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'cod_participant_specialty',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'ORIGEN_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'CODIGO_CENTRO',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 16)
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn('FECHA_INICIO', \Doctrine\DBAL\Types\Type::DATE);
$sepeParticipantsSpecialtyTutorialsTable->addColumn('FECHA_FIN', \Doctrine\DBAL\Types\Type::DATE);
$sepeParticipantsSpecialtyTutorialsTable->setPrimaryKey(array('cod'));
$sepeParticipantsSpecialtyTutorialsTable->addForeignKeyConstraint(
    $sepeParticipantsSpecialtyTable,
    array('cod_participant_specialty'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);

/* ==========    PLUGIN_SEPE_COURSE_ACTIONS   ========== */
$sepeCourseActionsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_COURSE_ACTIONS);
$sepeCourseActionsTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeCourseActionsTable->addColumn(
    'id_course',
       \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeCourseActionsTable->addColumn(
    'cod_action',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeCourseActionsTable->setPrimaryKey(array('cod'));
$sepeCourseActionsTable->addForeignKeyConstraint(
    $sepeActionsTable,
    array('cod_action'),
    array('cod'),
    array('onDelete' => 'CASCADE')
);

/* ==========    PLUGIN_SEPE_COMPETENCIA_DOCENTE   ========== */
$sepeCompetenciaDocenteTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_COMPETENCIA_DOCENTE);
$sepeCompetenciaDocenteTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeCompetenciaDocenteTable->addColumn(
    'code',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 2)
);
$sepeCompetenciaDocenteTable->addColumn('valor', \Doctrine\DBAL\Types\Type::TEXT);
$sepeCompetenciaDocenteTable->setPrimaryKey(array('cod'));

/* ==========    PLUGIN_SEPE_LOG_PARTICIPANT   ========== */
$sepeLogParticipantTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_LOG_PARTICIPANT);
$sepeLogParticipantTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeLogParticipantTable->addColumn(
    'cod_user_chamilo',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeLogParticipantTable->addColumn(
    'cod_action',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeLogParticipantTable->addColumn('fecha_alta', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogParticipantTable->addColumn('fecha_baja', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogParticipantTable->setPrimaryKey(array('cod'));

/* ==========    PLUGIN_SEPE_LOG_MOD_PARTICIPANT   ========== */
$sepeLogModParticipantTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_LOG_MOD_PARTICIPANT);
$sepeLogModParticipantTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeLogModParticipantTable->addColumn(
    'cod_user_chamilo',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeLogModParticipantTable->addColumn(
    'cod_action',
    \Doctrine\DBAL\Types\Type::INTEGER,
    array('unsigned' => true)
);
$sepeLogModParticipantTable->addColumn('fecha_mod', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogModParticipantTable->setPrimaryKey(array('cod'));

/* ==========    PLUGIN_SEPE_LOG   ========== */
$sepeLogTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_LOG);
$sepeLogTable->addColumn(
        'cod',
        \Doctrine\DBAL\Types\Type::INTEGER, 
        array('autoincrement' => true, 'unsigned' => true)
);
$sepeLogTable->addColumn(
    'ip',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 200)
);
$sepeLogTable->addColumn(
    'action',
    \Doctrine\DBAL\Types\Type::STRING,
    array('length' => 255)
);
$sepeLogTable->addColumn('fecha', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogTable->setPrimaryKey(array('cod'));

    
$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

//Insert data
$sepeCompetenciaDocenteTable = Database::get_main_table(SepePlugin::TABLE_SEPE_COMPETENCIA_DOCENTE);
$competencias = array(
    array(1, '01', 'Certificado de profesionalidad de docencia de la formación profesional para el empleo regulado por Real Decreto 1697/2011, de 18 de noviembre.'),
    array(2, '02', 'Certificado de profesionalidad de formador ocupacional.'),
    array(3, '03', 'Certificado de Aptitud Pedagágica o título profesional de Especialización Didáctica o Certificado de Cualificación Pedagógica.'),
    array(4, '04', 'Máster Universitario habilitante para el ejercicio de las Profesiones reguladas de Profesor de Educación Secundaria Obligatoria y Bachillerato, Formación Profesional y Escuelas Oficiales de Idiomas.'),
    array(5, '05', 'Curso de formación equivalente a la formación pedagógica y didáctica exigida para aquellas personas que, estando en posesión de una titulación declarada equivalente a efectos de docencia, no pueden realizar los estudios de máster, establecida en la disposición adicional primera del Real Decreto 1834/2008, de 8 de noviembre.'),
    array(6, '06', 'Experiencia docente contrastada de al menos 600 horas de impartición de acciones formativas de formación profesional para el empleo o del sistema educativo en modalidad presencial, en los últimos diez años.')
);

foreach ($competencias as $competencia) {
    Database::insert(
        $sepeCompetenciaDocenteTable,
        array(
            'cod' => $competencia[0],
            'code' => $competencia[1],
            'valor' => $competencia[2]

        )
    );
}

$sepeTutorsEmpresaTable = Database::get_main_table(SepePlugin::TABLE_SEPE_TUTORS_EMPRESA);
 Database::insert(
        $sepeTutorsEmpresaTable,
        array(
            'cod' => 1,
            'alias' => 'Sin tutor',
            'empresa' => 'SI',
            'formacion' => 'SI'
        )
    );

/* Crear campos extras a los usuarios de la plataforma */

$fieldlabel = 'sexo';
$fieldtype = '3';
$fieldtitle = 'Género';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);
$sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', 'Hombre', 'Hombre',1);";
Database::query($sql);
$sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', 'Mujer', 'Mujer',2);";
Database::query($sql);
$sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', 'Otros', 'Otros',3);";
Database::query($sql);

$fieldlabel = 'edad';
$fieldtype = '6';
$fieldtitle = 'Fecha de nacimiento';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'nivel_formativo';
$fieldtype = '1';
$fieldtitle = 'Nivel formativo';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'situacion_laboral';
$fieldtype = '1';
$fieldtitle = 'Situación Laboral';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'provincia_residencia';
$fieldtype = '4';
$fieldtitle = 'Provincia Residencia';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$provincias = 'Albacete;Alicante/Alacant;Almería;Araba/Álava;Asturias;Ávila;Badajoz;Balears, Illes;Barcelona;Bizkaia;Burgos;Cáceres;Cádiz;Cantabria;Castellón/Castelló;Ciudad Real;Córdoba;Coruña, A;Cuenca;Gipuzkoa;Girona;Granada;Guadalajara;Huelva;Huesca;Jaén;León;Lleida;Lugo;Madrid;Málaga;Murcia;Navarra;Ourense;Palencia;Palmas, Las;Pontevedr;Rioja, La;Salamanca;Santa Cruz de Tenerife;Segovia;Sevilla;Soria;Tarragona;Teruel;Toledo;Valencia/Valéncia;Valladolid;Zamora;Zaragoza;Ceuta;Melilla';
$list_provincias = explode(';',$provincias);
$i = 1;
foreach($list_provincias as $value){
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'comunidad_residencia';
$fieldtype = '4';
$fieldtitle = 'Comunidad autonoma de residencia';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);
$ccaa = ';Andalucía;Aragón;Asturias, Principado de;Balears, Illes;Canarias;Cantabria;Castilla y León;Castilla - La Mancha;Cataluña;Comunitat Valenciana;Extremadura;Galicia;Madrid, Comunidad de;Murcia, Región de;Navarra, Comunidad Foral de;País Vasco;Rioja, La;Ceuta;Melilla';
$list_ccaa = explode(';',$ccaa);
$i = 1;
foreach($list_ccaa as $value){
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}


$fieldlabel = 'provincia_trabajo';
$fieldtype = '4';
$fieldtitle = 'Provincia Trabajo';
$fielddefault = '';
//$fieldoptions = ';Albacete;Alicante/Alacant;Almería;Araba/Álava;Asturias;Ávila;Badajoz;Balears, Illes;Barcelona;Bizkaia;Burgos;Cáceres;Cádiz;Cantabria;Castellón/Castelló;Ciudad Real;Córdoba;Coruña, A;Cuenca;Gipuzkoa;Girona;Granada;Guadalajara;Huelva;Huesca;Jaén;León;Lleida;Lugo;Madrid;Málaga;Murcia;Navarra;Ourense;Palencia;Palmas, Las;Pontevedr;Rioja, La;Salamanca;Santa Cruz de Tenerife;Segovia;Sevilla;Soria;Tarragona;Teruel;Toledo;Valencia/Valéncia;Valladolid;Zamora;Zaragoza;Ceuta;Melilla';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);
$i = 1;
foreach($list_provincias as $value){
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'comunidad_trabajo';
$fieldtype = '4';
$fieldtitle = 'Comunidad autonoma Trabajo';
$fielddefault = '';
//$fieldoptions = ';Andalucía;Aragón;Asturias, Principado de;Balears, Illes;Canarias;Cantabria;Castilla y León;Castilla - La Mancha;Cataluña;Comunitat Valenciana;Extremadura;Galicia;Madrid, Comunidad de;Murcia, Región de;Navarra, Comunidad Foral de;País Vasco;Rioja, La;Ceuta;Melilla';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);
$i = 1;
foreach($list_ccaa as $value){
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'medio_conocimiento';
$fieldtype = '2';
$fieldtitle = 'Medio de conocimiento Acción formativa';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'experiencia_anterior';
$fieldtype = '2';
$fieldtitle = 'Experiencia anterior en la realización de cursos on-line';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'razones_teleformacion';
$fieldtype = '2';
$fieldtitle = 'Razones por la modalidad teleformación';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'valoracion_modalidad';
$fieldtype = '2';
$fieldtitle = 'Valoración general sobre la modalidad';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'categoria_profesional';
$fieldtype = '1';
$fieldtitle = 'Categoría profesional';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'tamano_empresa';
$fieldtype = '1';
$fieldtitle = 'Tamaño de la empresa';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);

$fieldlabel = 'horario_accion_formativa';
$fieldtype = '1';
$fieldtitle = 'Horario de la acción formativa';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);
