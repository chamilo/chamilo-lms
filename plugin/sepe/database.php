<?php
/* For license terms, see /license.txt */
/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php.
 *
 * @package chamilo.plugin.sepe
 */

/**
 * Check if script can be called.
 */
if (!function_exists('api_get_path')) {
    exit('This script must be loaded through the Chamilo plugin installer sequence');
}

$entityManager = Database::getManager();
$pluginSchema = new \Doctrine\DBAL\Schema\Schema();
$connection = $entityManager->getConnection();
$platform = $connection->getDatabasePlatform();

//Create tables
/* ========== PLUGIN_SEPE_CENTER ========== */
$sepeCenterTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_CENTER);
$sepeCenterTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeCenterTable->addColumn('center_origin', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('center_code', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('center_name', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('url', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('tracking_url', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('phone', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->addColumn('mail', \Doctrine\DBAL\Types\Type::STRING);
$sepeCenterTable->setPrimaryKey(['id']);

/* ========== PLUGIN_SEPE_ACTIONS ========== */
$sepeActionsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_ACTIONS);
$sepeActionsTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeActionsTable->addColumn(
    'action_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeActionsTable->addColumn(
    'action_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 30]
);
$sepeActionsTable->addColumn(
    'situation',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeActionsTable->addColumn(
    'specialty_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeActionsTable->addColumn(
    'professional_area',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 4]
);
$sepeActionsTable->addColumn(
    'specialty_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 14]
);
$sepeActionsTable->addColumn(
    'duration',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeActionsTable->addColumn('start_date', \Doctrine\DBAL\Types\Type::DATE);
$sepeActionsTable->addColumn('end_date', \Doctrine\DBAL\Types\Type::DATE);
$sepeActionsTable->addColumn(
    'full_itinerary_indicator',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeActionsTable->addColumn(
    'financing_type',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeActionsTable->addColumn(
    'attendees_count',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeActionsTable->addColumn(
    'action_name',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 250]
);
$sepeActionsTable->addColumn('global_info', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->addColumn('schedule', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->addColumn('requirements', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->addColumn('contact_action', \Doctrine\DBAL\Types\Type::TEXT);
$sepeActionsTable->setPrimaryKey(['id']);

/* ==========PLUGIN_SEPE_SPECIALTY========== */
$sepeSpecialtyTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_SPECIALTY);
$sepeSpecialtyTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeSpecialtyTable->addColumn(
    'action_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTable->addColumn(
    'specialty_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeSpecialtyTable->addColumn(
    'professional_area',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 4]
);
$sepeSpecialtyTable->addColumn(
    'specialty_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 14]
);
$sepeSpecialtyTable->addColumn(
    'center_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeSpecialtyTable->addColumn(
    'center_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 16]
);
$sepeSpecialtyTable->addColumn('start_date', \Doctrine\DBAL\Types\Type::DATE);
$sepeSpecialtyTable->addColumn('end_date', \Doctrine\DBAL\Types\Type::DATE);
$sepeSpecialtyTable->addColumn(
    'modality_impartition',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeSpecialtyTable->addColumn(
    'classroom_hours',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTable->addColumn(
    'distance_hours',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTable->addColumn(
    'mornings_participants_number',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'mornings_access_number',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'morning_total_duration',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'afternoon_participants_number',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'afternoon_access_number',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'afternoon_total_duration',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'night_participants_number',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'night_access_number',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'night_total_duration',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'attendees_count',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'learning_activity_count',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'attempt_count',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->addColumn(
    'evaluation_activity_count',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeSpecialtyTable->setPrimaryKey(['id']);
$sepeSpecialtyTable->addForeignKeyConstraint(
    $sepeActionsTable,
    ['action_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_CENTROS ========== */
$sepeCentrosTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_CENTERS);
$sepeCentrosTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeCentrosTable->addColumn(
    'center_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeCentrosTable->addColumn(
    'center_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 16]
);
$sepeCentrosTable->setPrimaryKey(['id']);

/* ========== PLUGIN_SEPE_SPECIALTY_CLASSROOM ========== */
$sepeSpecialtyClassroomTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_SPECIALTY_CLASSROOM);
$sepeSpecialtyClassroomTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeSpecialtyClassroomTable->addColumn(
    'specialty_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyClassroomTable->addColumn(
    'center_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyClassroomTable->setPrimaryKey(['id']);
$sepeSpecialtyClassroomTable->addForeignKeyConstraint(
    $sepeSpecialtyTable,
    ['specialty_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_TUTORS ========== */
$sepeTutorsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_TUTORS);
$sepeTutorsTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeTutorsTable->addColumn(
    'platform_user_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeTutorsTable->addColumn(
    'document_type',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1]
); //enum('D','E','U','W','G','H')
$sepeTutorsTable->addColumn(
    'document_number',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 10]
);
$sepeTutorsTable->addColumn(
    'document_letter',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1]
);
$sepeTutorsTable->addColumn(
    'tutor_accreditation',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 200]
);
$sepeTutorsTable->addColumn(
    'professional_experience',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeTutorsTable->addColumn(
    'teaching_competence',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeTutorsTable->addColumn(
    'experience_teleforming',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeTutorsTable->addColumn(
    'training_teleforming',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeTutorsTable->setPrimaryKey(['id']);

/* ========== PLUGIN_SEPE_SPECIALTY_TUTORS ========== */
$sepeSpecialtyTutorsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_SPECIALTY_TUTORS);
$sepeSpecialtyTutorsTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeSpecialtyTutorsTable->addColumn(
    'specialty_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTutorsTable->addColumn(
    'tutor_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTutorsTable->addColumn(
    'tutor_accreditation',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 200]
);
$sepeSpecialtyTutorsTable->addColumn(
    'professional_experience',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTutorsTable->addColumn(
    'teaching_competence',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeSpecialtyTutorsTable->addColumn(
    'experience_teleforming',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeSpecialtyTutorsTable->addColumn(
    'training_teleforming',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeSpecialtyTutorsTable->setPrimaryKey(['id']);
$sepeSpecialtyTutorsTable->addForeignKeyConstraint(
    $sepeSpecialtyTable,
    ['specialty_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_TUTORS_EMPRESA ========== */
$sepeTutorsCompanyTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_TUTORS_COMPANY);
$sepeTutorsCompanyTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeTutorsCompanyTable->addColumn(
    'alias',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 255]
);
$sepeTutorsCompanyTable->addColumn(
    'document_type',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1, 'notnull' => false]
); //enum('D','E','U','W','G','H')
$sepeTutorsCompanyTable->addColumn(
    'document_number',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 10, 'notnull' => false]
);
$sepeTutorsCompanyTable->addColumn(
    'document_letter',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1, 'notnull' => false]
);
$sepeTutorsCompanyTable->addColumn(
    'company',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeTutorsCompanyTable->addColumn(
    'training',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeTutorsCompanyTable->setPrimaryKey(['id']);

/* ========== PLUGIN_SEPE_PARTICIPANTS ========== */
$sepeParticipantsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_PARTICIPANTS);
$sepeParticipantsTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeParticipantsTable->addColumn(
    'action_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeParticipantsTable->addColumn(
    'platform_user_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeParticipantsTable->addColumn(
    'document_type',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1]
); //enum('D','E','U','W','G','H')
$sepeParticipantsTable->addColumn(
    'document_number',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 10]
);
$sepeParticipantsTable->addColumn(
    'document_letter',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1]
);
$sepeParticipantsTable->addColumn(
    'key_competence',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeParticipantsTable->addColumn(
    'contract_id',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 14, 'notnull' => false]
);
$sepeParticipantsTable->addColumn(
    'company_fiscal_number',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 9, 'notnull' => false]
);
$sepeParticipantsTable->addColumn(
    'company_tutor_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeParticipantsTable->addColumn(
    'training_tutor_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true, 'notnull' => false]
);
$sepeParticipantsTable->setPrimaryKey(['id']);
$sepeParticipantsTable->addForeignKeyConstraint(
    $sepeActionsTable,
    ['action_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);
$sepeParticipantsTable->addForeignKeyConstraint(
    $sepeTutorsCompanyTable,
    ['company_tutor_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);
$sepeParticipantsTable->addForeignKeyConstraint(
    $sepeTutorsCompanyTable,
    ['training_tutor_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_PARTICIPANTS_SPECIALTY ========== */
$sepeParticipantsSpecialtyTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY);
$sepeParticipantsSpecialtyTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'participant_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'specialty_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'professional_area',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 4, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'specialty_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 14, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'registration_date',
    \Doctrine\DBAL\Types\Type::DATE,
    ['notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'leaving_date',
    \Doctrine\DBAL\Types\Type::DATE,
    ['notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'center_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'center_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 16, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'start_date',
    \Doctrine\DBAL\Types\Type::DATE,
    ['notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'end_date',
    \Doctrine\DBAL\Types\Type::DATE,
    ['notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'final_result',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 1, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'final_qualification',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 4, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->addColumn(
    'final_score',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 4, 'notnull' => false]
);
$sepeParticipantsSpecialtyTable->setPrimaryKey(['id']);
$sepeParticipantsSpecialtyTable->addForeignKeyConstraint(
    $sepeParticipantsTable,
    ['participant_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS ========== */
$sepeParticipantsSpecialtyTutorialsTable = $pluginSchema->createTable(
    SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'participant_specialty_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'center_origin',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn(
    'center_code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 16]
);
$sepeParticipantsSpecialtyTutorialsTable->addColumn('start_date', \Doctrine\DBAL\Types\Type::DATE);
$sepeParticipantsSpecialtyTutorialsTable->addColumn('end_date', \Doctrine\DBAL\Types\Type::DATE);
$sepeParticipantsSpecialtyTutorialsTable->setPrimaryKey(['id']);
$sepeParticipantsSpecialtyTutorialsTable->addForeignKeyConstraint(
    $sepeParticipantsSpecialtyTable,
    ['participant_specialty_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_COURSE_ACTIONS ========== */
$sepeCourseActionsTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_COURSE_ACTIONS);
$sepeCourseActionsTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeCourseActionsTable->addColumn(
    'course_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeCourseActionsTable->addColumn(
    'action_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeCourseActionsTable->setPrimaryKey(['id']);
$sepeCourseActionsTable->addForeignKeyConstraint(
    $sepeActionsTable,
    ['action_id'],
    ['id'],
    ['onDelete' => 'CASCADE']
);

/* ========== PLUGIN_SEPE_TEACHING_COMPETENCE ========== */
$sepeTeachingCompetenceTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_TEACHING_COMPETENCE);
$sepeTeachingCompetenceTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeTeachingCompetenceTable->addColumn(
    'code',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 2]
);
$sepeTeachingCompetenceTable->addColumn('value', \Doctrine\DBAL\Types\Type::TEXT);
$sepeTeachingCompetenceTable->setPrimaryKey(['id']);

/* ========== PLUGIN_SEPE_LOG_PARTICIPANT ========== */
$sepeLogParticipantTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_LOG_PARTICIPANT);
$sepeLogParticipantTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeLogParticipantTable->addColumn(
    'platform_user_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeLogParticipantTable->addColumn(
    'action_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeLogParticipantTable->addColumn('registration_date', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogParticipantTable->addColumn('leaving_date', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogParticipantTable->setPrimaryKey(['id']);

/* ========== PLUGIN_SEPE_LOG_MOD_PARTICIPANT ========== */
$sepeLogModParticipantTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_LOG_MOD_PARTICIPANT);
$sepeLogModParticipantTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeLogModParticipantTable->addColumn(
    'platform_user_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeLogModParticipantTable->addColumn(
    'action_id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['unsigned' => true]
);
$sepeLogModParticipantTable->addColumn('change_date', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogModParticipantTable->setPrimaryKey(['id']);

/* ==========PLUGIN_SEPE_LOG   ========== */
$sepeLogTable = $pluginSchema->createTable(SepePlugin::TABLE_SEPE_LOG);
$sepeLogTable->addColumn(
    'id',
    \Doctrine\DBAL\Types\Type::INTEGER,
    ['autoincrement' => true, 'unsigned' => true]
);
$sepeLogTable->addColumn(
    'ip',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 200]
);
$sepeLogTable->addColumn(
    'action',
    \Doctrine\DBAL\Types\Type::STRING,
    ['length' => 255]
);
$sepeLogTable->addColumn('date', \Doctrine\DBAL\Types\Type::DATETIME);
$sepeLogTable->setPrimaryKey(['id']);

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

//Insert data
$sepeTeachingCompetenceTable = Database::get_main_table(SepePlugin::TABLE_SEPE_TEACHING_COMPETENCE);
$competences = [
    [
        1,
        '01',
        'Certificado de profesionalidad de docencia de la formación profesional para el empleo regulado por Real Decreto 1697/2011, de 18 de noviembre.',
    ],
    [2, '02', 'Certificado de profesionalidad de formador ocupacional.'],
    [
        3,
        '03',
        'Certificado de Aptitud Pedagógica o título profesional de Especialización Didáctica o Certificado de Cualificación Pedagógica.',
    ],
    [
        4,
        '04',
        'Máster Universitario habilitante para el ejercicio de las Profesiones reguladas de Profesor de Educación Secundaria Obligatoria y Bachillerato, Formación Profesional y Escuelas Oficiales de Idiomas.',
    ],
    [
        5,
        '05',
        'Curso de formación equivalente a la formación pedagógica y didáctica exigida para aquellas personas que, estando en posesion de una titulación declarada equivalente a efectos de docencia, no pueden realizar los estudios de máster, establecida en la disposición adicional primera del Real Decreto 1834/2008, de 8 de noviembre.',
    ],
    [
        6,
        '06',
        'Experiencia docente contrastada de al menos 600 horas de impartición de acciones formativas de formación profesional para el empleo o del sistema educativo en modalidad presencial, en los últimos diez años.',
    ],
];

foreach ($competences as $competence) {
    Database::insert(
        $sepeTeachingCompetenceTable,
        [
            'id' => $competence[0],
            'code' => $competence[1],
            'value' => $competence[2],
        ]
    );
}

$sepeTutorsCompanyTable = Database::get_main_table(SepePlugin::TABLE_SEPE_TUTORS_COMPANY);
Database::insert(
    $sepeTutorsCompanyTable,
    [
        'id' => 1,
        'alias' => 'Sin tutor',
        'company' => 'SI',
        'training' => 'SI',
    ]
);

/* Create extra fields for platform users */
$fieldlabel = 'sexo';
$fieldtype = '3';
$fieldtitle = 'Género';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);
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
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'nivel_formativo';
$fieldtype = '1';
$fieldtitle = 'Nivel formativo';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'situacion_laboral';
$fieldtype = '1';
$fieldtitle = 'Situación Laboral';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'provincia_residencia';
$fieldtype = '4';
$fieldtitle = 'Provincia Residencia';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$provinces = 'Albacete;Alicante/Alacant;Almería;Araba/Álava;Asturias;Ávila;Badajoz;Balears, Illes;Barcelona;Bizkaia;Burgos;Cáceres;Cádiz;Cantabria;Castellón/Castelló;Ciudad Real;Córdoba;Coruña, A;Cuenca;Gipuzkoa;Girona;Granada;Guadalajara;Huelva;Huesca;Jaén;León;Lleida;Lugo;Madrid;Málaga;Murcia;Navarra;Ourense;Palencia;Palmas, Las;Pontevedr;Rioja, La;Salamanca;Santa Cruz de Tenerife;Segovia;Sevilla;Soria;Tarragona;Teruel;Toledo;Valencia/Valéncia;Valladolid;Zamora;Zaragoza;Ceuta;Melilla';
$list_provinces = explode(';', $provinces);
$i = 1;
foreach ($list_provinces as $value) {
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'comunidad_residencia';
$fieldtype = '4';
$fieldtitle = 'Comunidad autonoma de residencia';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);
$ccaa = ';Andalucía;Aragón;Asturias, Principado de;Balears, Illes;Canarias;Cantabria;Castilla y León;Castilla - La Mancha;Cataluña;Comunitat Valenciana;Extremadura;Galicia;Madrid, Comunidad de;Murcia, Región de;Navarra, Comunidad Foral de;País Vasco;Rioja, La;Ceuta;Melilla';
$list_ccaa = explode(';', $ccaa);
$i = 1;
foreach ($list_ccaa as $value) {
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'provincia_trabajo';
$fieldtype = '4';
$fieldtitle = 'Provincia Trabajo';
$fielddefault = '';
//$fieldoptions = ';Albacete;Alicante/Alacant;Almería;Araba/Álava;Asturias;Ávila;Badajoz;Balears, Illes;Barcelona;Bizkaia;Burgos;Cáceres;Cádiz;Cantabria;Castellón/Castelló;Ciudad Real;Córdoba;Coruña, A;Cuenca;Gipuzkoa;Girona;Granada;Guadalajara;Huelva;Huesca;Jaén;León;Lleida;Lugo;Madrid;Málaga;Murcia;Navarra;Ourense;Palencia;Palmas, Las;Pontevedr;Rioja, La;Salamanca;Santa Cruz de Tenerife;Segovia;Sevilla;Soria;Tarragona;Teruel;Toledo;Valencia/Valéncia;Valladolid;Zamora;Zaragoza;Ceuta;Melilla';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);
$i = 1;
foreach ($list_provinces as $value) {
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'comunidad_trabajo';
$fieldtype = '4';
$fieldtitle = 'Comunidad autonoma Trabajo';
$fielddefault = '';
//$fieldoptions = ';Andalucía;Aragón;Asturias, Principado de;Balears, Illes;Canarias;Cantabria;Castilla y León;Castilla - La Mancha;Cataluña;Comunitat Valenciana;Extremadura;Galicia;Madrid, Comunidad de;Murcia, Región de;Navarra, Comunidad Foral de;País Vasco;Rioja, La;Ceuta;Melilla';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);
$i = 1;
foreach ($list_ccaa as $value) {
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ('".$field_id."', '".$i."', '".$value."','".$i."');";
    Database::query($sql);
    $i++;
}

$fieldlabel = 'medio_conocimiento';
$fieldtype = '2';
$fieldtitle = 'Medio de conocimiento Acción formativa';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'experiencia_anterior';
$fieldtype = '2';
$fieldtitle = 'Experiencia anterior en la realización de cursos on-line';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'razones_teleformacion';
$fieldtype = '2';
$fieldtitle = 'Razones por la modalidad teleformación';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'valoracion_modalidad';
$fieldtype = '2';
$fieldtitle = 'Valoración general sobre la modalidad';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'categoria_profesional';
$fieldtype = '1';
$fieldtitle = 'Categoría profesional';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'tamano_empresa';
$fieldtype = '1';
$fieldtitle = 'Tamaño de la empresa';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'horario_accion_formativa';
$fieldtype = '1';
$fieldtitle = 'Horario de la acción formativa';
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);
