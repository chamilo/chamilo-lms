<?php
/* For license terms, see /license.txt */
/**
 * Plugin class for the SEPE plugin
 * @package chamilo.plugin.sepe
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
 * @author Julio Montoya <gugli100@gmail.com>
 */
class SepePlugin extends Plugin
{
    const TABLE_SEPE_CENTER = 'plugin_sepe_center';
    const TABLE_SEPE_ACTIONS = 'plugin_sepe_actions';
    const TABLE_SEPE_SPECIALTY = 'plugin_sepe_specialty';
    const TABLE_SEPE_SPECIALTY_CLASSROOM = 'plugin_sepe_specialty_classroom';
    const TABLE_SEPE_CENTROS = 'plugin_sepe_centros';
    const TABLE_SEPE_TUTORS = 'plugin_sepe_tutors';
    const TABLE_SEPE_SPECIALTY_TUTORS = 'plugin_sepe_specialty_tutors';
    const TABLE_SEPE_PARTICIPANTS = 'plugin_sepe_participants';
    const TABLE_SEPE_PARTICIPANTS_SPECIALTY = 'plugin_sepe_participants_specialty';
    const TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS = 'plugin_sepe_participants_specialty_tutorials';
    const TABLE_SEPE_COURSE_ACTIONS = 'plugin_sepe_course_actions';
    const TABLE_SEPE_TUTORS_EMPRESA = 'plugin_sepe_tutors_empresa';
    const TABLE_SEPE_COMPETENCIA_DOCENTE = 'plugin_sepe_competencia_docente';
    const TABLE_SEPE_LOG_PARTICIPANT = 'plugin_sepe_log_participant';
    const TABLE_SEPE_LOG_MOD_PARTICIPANT = 'plugin_sepe_log_mod_participant';
    const TABLE_SEPE_LOG = 'plugin_sepe_log';
    
    public $isAdminPlugin = true;
    /**
     *
     * @return StaticPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct()
    {
        parent::__construct(
            '1.0', 
            '
                Jose Angel Ruiz - NoSoloRed (original author) <br>
                Julio Montoya (SOAP integration)
            ', 
            array('sepe_enable' => 'boolean')
        );
    }
    
    /**
     * This method creates the tables required to this plugin
     */
    function install()
    {
        $tablesToBeCompared = array(
            self::TABLE_SEPE_CENTER,
            self::TABLE_SEPE_ACTIONS,
            self::TABLE_SEPE_SPECIALTY,
            self::TABLE_SEPE_SPECIALTY_CLASSROOM,
            self::TABLE_SEPE_CENTROS,
            self::TABLE_SEPE_TUTORS,
            self::TABLE_SEPE_SPECIALTY_TUTORS,
            self::TABLE_SEPE_PARTICIPANTS,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS,
            self::TABLE_SEPE_COURSE_ACTIONS,
            self::TABLE_SEPE_TUTORS_EMPRESA,
            self::TABLE_SEPE_COMPETENCIA_DOCENTE,
            self::TABLE_SEPE_LOG_PARTICIPANT,
            self::TABLE_SEPE_LOG_MOD_PARTICIPANT,
            self::TABLE_SEPE_LOG
        );
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->getSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if ($tables) {
            return false;
        }

        require_once api_get_path(SYS_PLUGIN_PATH) . 'sepe/database.php';
    }
        
    /**
     * This method drops the plugin tables
     */
    function uninstall()
    {
        $tablesToBeDeleted = array(
            self::TABLE_SEPE_CENTER,
            self::TABLE_SEPE_SPECIALTY_CLASSROOM,
            self::TABLE_SEPE_CENTROS,
            self::TABLE_SEPE_TUTORS,
            self::TABLE_SEPE_SPECIALTY_TUTORS,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS,
            self::TABLE_SEPE_PARTICIPANTS_SPECIALTY,
            self::TABLE_SEPE_COURSE_ACTIONS,
            self::TABLE_SEPE_PARTICIPANTS,
            self::TABLE_SEPE_TUTORS_EMPRESA,
            self::TABLE_SEPE_SPECIALTY,
            self::TABLE_SEPE_ACTIONS,
            self::TABLE_SEPE_COMPETENCIA_DOCENTE,
            self::TABLE_SEPE_LOG_PARTICIPANT,
            self::TABLE_SEPE_LOG_MOD_PARTICIPANT,
            self::TABLE_SEPE_LOG
        );

        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

}
