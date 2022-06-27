<?php
/* For license terms, see /license.txt */

/**
 * Plugin class for the SEPE plugin.
 *
 * @package chamilo.plugin.sepe
 *
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
 * @author Julio Montoya <gugli100@gmail.com>
 */
class SepePlugin extends Plugin
{
    public const TABLE_SEPE_CENTER = 'plugin_sepe_center';
    public const TABLE_SEPE_ACTIONS = 'plugin_sepe_actions';
    public const TABLE_SEPE_SPECIALTY = 'plugin_sepe_specialty';
    public const TABLE_SEPE_SPECIALTY_CLASSROOM = 'plugin_sepe_specialty_classroom';
    public const TABLE_SEPE_CENTERS = 'plugin_sepe_centers';
    public const TABLE_SEPE_TUTORS = 'plugin_sepe_tutors';
    public const TABLE_SEPE_SPECIALTY_TUTORS = 'plugin_sepe_specialty_tutors';
    public const TABLE_SEPE_PARTICIPANTS = 'plugin_sepe_participants';
    public const TABLE_SEPE_PARTICIPANTS_SPECIALTY = 'plugin_sepe_participants_specialty';
    public const TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS = 'plugin_sepe_participants_specialty_tutorials';
    public const TABLE_SEPE_COURSE_ACTIONS = 'plugin_sepe_course_actions';
    public const TABLE_SEPE_TUTORS_COMPANY = 'plugin_sepe_tutors_company';
    public const TABLE_SEPE_TEACHING_COMPETENCE = 'plugin_sepe_teaching_competence';
    public const TABLE_SEPE_LOG_PARTICIPANT = 'plugin_sepe_log_participant';
    public const TABLE_SEPE_LOG_MOD_PARTICIPANT = 'plugin_sepe_log_mod_participant';
    public const TABLE_SEPE_LOG = 'plugin_sepe_log';

    public $isAdminPlugin = true;

    protected function __construct()
    {
        parent::__construct(
            '2.1',
            '
                Jose Angel Ruiz - NoSoloRed (original author) <br>
                Julio Montoya (SOAP integration)
            ',
            ['sepe_enable' => 'boolean']
        );
    }

    /**
     * @return SepePlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin.
     */
    public function install()
    {
        $tablesToBeCompared = [
            self::TABLE_SEPE_CENTER,
            self::TABLE_SEPE_ACTIONS,
            self::TABLE_SEPE_SPECIALTY,
            self::TABLE_SEPE_SPECIALTY_CLASSROOM,
            self::TABLE_SEPE_CENTERS,
            self::TABLE_SEPE_TUTORS,
            self::TABLE_SEPE_SPECIALTY_TUTORS,
            self::TABLE_SEPE_PARTICIPANTS,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS,
            self::TABLE_SEPE_COURSE_ACTIONS,
            self::TABLE_SEPE_TUTORS_COMPANY,
            self::TABLE_SEPE_TEACHING_COMPETENCE,
            self::TABLE_SEPE_LOG_PARTICIPANT,
            self::TABLE_SEPE_LOG_MOD_PARTICIPANT,
            self::TABLE_SEPE_LOG,
        ];
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->getSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if (empty($tables)) {
            return false;
        }

        require_once api_get_path(SYS_PLUGIN_PATH).'sepe/database.php';
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        $tablesToBeDeleted = [
            self::TABLE_SEPE_CENTER,
            self::TABLE_SEPE_SPECIALTY_CLASSROOM,
            self::TABLE_SEPE_CENTERS,
            self::TABLE_SEPE_TUTORS,
            self::TABLE_SEPE_SPECIALTY_TUTORS,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY,
            self::TABLE_SEPE_COURSE_ACTIONS,
            self::TABLE_SEPE_PARTICIPANTS,
            self::TABLE_SEPE_TUTORS_COMPANY,
            self::TABLE_SEPE_SPECIALTY,
            self::TABLE_SEPE_ACTIONS,
            self::TABLE_SEPE_TEACHING_COMPETENCE,
            self::TABLE_SEPE_LOG_PARTICIPANT,
            self::TABLE_SEPE_LOG_MOD_PARTICIPANT,
            self::TABLE_SEPE_LOG,
        ];

        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

    /**
     * Update.
     */
    public function update()
    {
        $oldTableCenters = 'plugin_sepe_centros';
        $oldTableTutorsCompany = 'plugin_sepe_tutors_empresa';
        $oldTableCompetence = 'plugin_sepe_competencia_docente';
        $sql = "RENAME TABLE "
                   .$oldTableCenters." TO ".self::TABLE_SEPE_CENTERS.", "
                   .$oldTableTutorsCompany." TO ".self::TABLE_SEPE_TUTORS_COMPANY.", "
                   .$oldTableCompetence." TO ".self::TABLE_SEPE_TEACHING_COMPETENCE.";";
        Database::query($sql);

        $sepeCourseActionsTable = self::TABLE_SEPE_COURSE_ACTIONS;
        $sql = "ALTER TABLE ".$sepeCourseActionsTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCourseActionsTable."
                CHANGE `cod_action` `action_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCourseActionsTable."
                CHANGE `id_course` `course_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);

        $sepeActionsTable = self::TABLE_SEPE_ACTIONS;
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `ORIGEN_ACCION` `action_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `CODIGO_ACCION` `action_code` VARCHAR(30)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `SITUACION` `situation` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `ORIGEN_ESPECIALIDAD` `specialty_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `AREA_PROFESIONAL` `professional_area` VARCHAR(4)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `CODIGO_ESPECIALIDAD` `specialty_code` VARCHAR(14)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `DURACION` `duration` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `FECHA_INICIO` `start_date` DATE NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `FECHA_FIN` `end_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `IND_ITINERARIO_COMPLETO` `full_itinerary_indicator` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `TIPO_FINANCIACION` `financing_type` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `NUMERO_ASISTENTES` `attendees_count` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `DENOMINACION_ACCION` `action_name` VARCHAR(50)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `INFORMACION_GENERAL` `global_info` LONGTEXT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `HORARIOS` `schedule` LONGTEXT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `REQUISITOS` `requirements` LONGTEXT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeActionsTable."
                CHANGE `CONTACTO_ACCION` `contact_action` LONGTEXT";
        Database::query($sql);

        $sepeSpecialtyTable = self::TABLE_SEPE_SPECIALTY;
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `cod_action` `action_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `ORIGEN_ESPECIALIDAD` `specialty_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `AREA_PROFESIONAL` `professional_area` VARCHAR(4)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `CODIGO_ESPECIALIDAD` `specialty_code` VARCHAR(14)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `ORIGEN_CENTRO` `center_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `CODIGO_CENTRO` `center_code` VARCHAR(16)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `FECHA_INICIO` `start_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `FECHA_FIN` `end_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `MODALIDAD_IMPARTICION` `modality_impartition` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HORAS_PRESENCIAL` `classroom_hours` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HORAS_TELEFORMACION` `distance_hours` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HM_NUM_PARTICIPANTES` `mornings_participants_number` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HM_NUMERO_ACCESOS` `mornings_access_number` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HM_DURACION_TOTAL` `morning_total_duration` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HT_NUM_PARTICIPANTES` `afternoon_participants_number` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HT_NUMERO_ACCESOS` `afternoon_access_number` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HT_DURACION_TOTAL` `afternoon_total_duration` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HN_NUM_PARTICIPANTES` `night_participants_number` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HN_NUMERO_ACCESOS` `night_access_number` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `HN_DURACION_TOTAL` `night_total_duration` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `NUM_PARTICIPANTES` `attendees_count` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `NUMERO_ACTIVIDADES_APRENDIZAJE` `learning_activity_count` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `NUMERO_INTENTOS` `attempt_count` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTable."
                CHANGE `NUMERO_ACTIVIDADES_EVALUACION` `evaluation_activity_count` INT( 10 ) UNSIGNED";
        Database::query($sql);

        $sepeParticipantTable = self::TABLE_SEPE_PARTICIPANTS;
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `cod_action` `action_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `cod_tutor_empresa` `company_tutor_id` INT( 10 ) UNSIGNED NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `cod_tutor_formacion` `training_tutor_id` INT( 10 ) UNSIGNED NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `cod_user_chamilo` `platform_user_id` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `TIPO_DOCUMENTO` `document_type` VARCHAR( 1 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `NUM_DOCUMENTO` `document_number` VARCHAR( 10 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `LETRA_NIF` `document_letter` VARCHAR( 1 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `INDICADOR_COMPETENCIAS_CLAVE` `key_competence` VARCHAR( 2 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `ID_CONTRATO_CFA` `contract_id` VARCHAR( 14 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantTable."
                CHANGE `CIF_EMPRESA` `company_fiscal_number` VARCHAR( 9 )";
        Database::query($sql);

        $sepeCenterTable = self::TABLE_SEPE_CENTERS;
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `ORIGEN_CENTRO` `center_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `CODIGO_CENTRO` `center_code` VARCHAR(16)";
        Database::query($sql);

        $sepeSpecialtyClassroomTable = self::TABLE_SEPE_SPECIALTY_CLASSROOM;
        $sql = "ALTER TABLE ".$sepeSpecialtyClassroomTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyClassroomTable."
                CHANGE `cod_specialty` `specialty_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyClassroomTable."
                CHANGE `cod_centro` `center_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);

        $sepeSpecialtyTutorsTable = self::TABLE_SEPE_SPECIALTY_TUTORS;
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `cod_specialty` `specialty_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `cod_tutor` `tutor_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `ACREDITACION_TUTOR` `tutor_accreditation` VARCHAR(200)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `EXPERIENCIA_PROFESIONAL` `professional_experience` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `COMPETENCIA_DOCENTE` `teaching_competence` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `EXPERIENCIA_MODALIDAD_TELEFORMACION` `experience_teleforming` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeSpecialtyTutorsTable."
                CHANGE `FORMACION_MODALIDAD_TELEFORMACION` `training_teleforming` VARCHAR(2)";
        Database::query($sql);

        $sepeTutorsTable = self::TABLE_SEPE_TUTORS;
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `cod_user_chamilo` `platform_user_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `TIPO_DOCUMENTO` `document_type` VARCHAR( 1 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `NUM_DOCUMENTO` `document_number` VARCHAR( 10 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `LETRA_NIF` `document_letter` VARCHAR( 1 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `ACREDITACION_TUTOR` `tutor_accreditation` VARCHAR(200)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `EXPERIENCIA_PROFESIONAL` `professional_experience` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `COMPETENCIA_DOCENTE` `teaching_competence` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `EXPERIENCIA_MODALIDAD_TELEFORMACION` `experience_teleforming` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsTable."
                CHANGE `FORMACION_MODALIDAD_TELEFORMACION` `training_teleforming` VARCHAR(2)";
        Database::query($sql);

        $sepeParticipantSpecialtyTable = self::TABLE_SEPE_PARTICIPANTS_SPECIALTY;
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `cod_participant` `participant_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `ORIGEN_ESPECIALIDAD` `specialty_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `AREA_PROFESIONAL` `professional_area` VARCHAR(4)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `CODIGO_ESPECIALIDAD` `specialty_code` VARCHAR(14)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `FECHA_ALTA` `registration_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `FECHA_BAJA` `leaving_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `ORIGEN_CENTRO` `center_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `CODIGO_CENTRO` `center_code` VARCHAR(16)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `FECHA_INICIO` `start_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `FECHA_FIN` `end_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `RESULTADO_FINAL` `final_result` VARCHAR(1)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `CALIFICACION_FINAL` `final_qualification` VARCHAR(4)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTable."
                CHANGE `PUNTUACION_FINAL` `final_score` VARCHAR(4)";
        Database::query($sql);

        $sepeParticipantSpecialtyTutorialsTable = self::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS;
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTutorialsTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTutorialsTable."
                CHANGE `cod_participant_specialty` `participant_specialty_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTutorialsTable."
                CHANGE `ORIGEN_CENTRO` `center_origin` VARCHAR(2)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTutorialsTable."
                CHANGE `CODIGO_CENTRO` `center_code` VARCHAR(16)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTutorialsTable."
                CHANGE `FECHA_INICIO` `start_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeParticipantSpecialtyTutorialsTable."
                CHANGE `FECHA_FIN` `end_date` DATE";
        Database::query($sql);

        $sepeTutorsCompanyTable = self::TABLE_SEPE_TUTORS_COMPANY;

        $sql = "UPDATE ".$sepeTutorsCompanyTable." SET empresa='1' WHERE empresa='SI'";
        Database::query($sql);
        $sql = "UPDATE ".$sepeTutorsCompanyTable." SET empresa='0' WHERE empresa='NO'";
        Database::query($sql);
        $sql = "UPDATE ".$sepeTutorsCompanyTable." SET formacion='1' WHERE formacion='SI'";
        Database::query($sql);
        $sql = "UPDATE ".$sepeTutorsCompanyTable." SET formacion='0' WHERE formacion='NO'";
        Database::query($sql);

        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `alias` `alias` VARCHAR(255)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `TIPO_DOCUMENTO` `document_type` VARCHAR( 1 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `NUM_DOCUMENTO` `document_number` VARCHAR( 10 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `LETRA_NIF` `document_letter` VARCHAR( 1 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `empresa` `company` VARCHAR(1)";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeTutorsCompanyTable."
                CHANGE `formacion` `training` VARCHAR(1)";
        Database::query($sql);

        $sepeCompetenceTable = self::TABLE_SEPE_TEACHING_COMPETENCE;
        $sql = "ALTER TABLE ".$sepeCompetenceTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCompetenceTable."
                CHANGE `valor` `value` LONGTEXT";
        Database::query($sql);

        $sepeLogParticipantTable = self::TABLE_SEPE_LOG_PARTICIPANT;
        $sql = "ALTER TABLE ".$sepeLogParticipantTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogParticipantTable."
                CHANGE `cod_user_chamilo` `platform_user_id` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogParticipantTable."
                CHANGE `cod_action` `action_id` INT( 10 ) UNSIGNED";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogParticipantTable."
                CHANGE `fecha_alta` `registration_date` DATE";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogParticipantTable."
                CHANGE `fecha_baja` `leaving_date` DATE";
        Database::query($sql);

        $sepeLogModParticipantTable = self::TABLE_SEPE_LOG_MOD_PARTICIPANT;
        $sql = "ALTER TABLE ".$sepeLogModParticipantTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogModParticipantTable."
                CHANGE `cod_user_chamilo` `platform_user_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogModParticipantTable."
                CHANGE `cod_action` `action_id` INT( 10 ) UNSIGNED NOT NULL";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeLogModParticipantTable."
                CHANGE `fecha_mod` `change_date` DATE";
        Database::query($sql);

        $sepeCenterTable = self::TABLE_SEPE_CENTER;
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `cod` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `origen_centro` `center_origin` VARCHAR( 255 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `codigo_centro` `center_code` VARCHAR( 255 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `nombre_centro` `center_name` VARCHAR( 255 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `url` `url` VARCHAR( 255 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `url_seguimiento` `tracking_url` VARCHAR( 255 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `telefono` `phone` VARCHAR( 255 )";
        Database::query($sql);
        $sql = "ALTER TABLE ".$sepeCenterTable."
                CHANGE `email` `mail` VARCHAR( 255 )";
        Database::query($sql);
    }
}
