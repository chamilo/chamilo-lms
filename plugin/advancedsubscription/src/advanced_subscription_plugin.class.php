<?php
/* For licensing terms, see /license.txt */
/**
 * @TODO: Improve description
 * This class is used to add an advanced subscription allowing the admin to
 * create user queues requesting a subscribe to a session
 * @package chamilo.plugin.advancedsubscription
 */

class AdvancedSubscriptionPlugin extends Plugin
{
    const ADVSUB_QUEUE_STATUS_START = 0;
    const ADVSUB_QUEUE_STATUS_BOSS_DISAPPROVED = 1;
    const ADVSUB_QUEUE_STATUS_BOSS_APPROVED = 2;
    const ADVSUB_QUEUE_STATUS_ADMIN_DISAPPROVED = 3;
    const ADVSUB_QUEUE_STATUS_ADMIN_APPROVED = 10;
    /**
     * Constructor
     */
    function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'yearly_cost_limit' => 'text',
            'yearly_hours_limit' => 'text',
            'yearly_cost_unit_converter' => 'text',
            'courses_count_limit' => 'text',
            'course_session_credit_year_start_date' => 'text',
            'ws_url' => 'text',
            'min_profile_percentage' => 'text',
            'check_induction' => 'boolean',
            'confirmation_message' => 'wysiwyg'
        );

        parent::__construct('1.0', 'Imanol Losada, Daniel Barreto', $parameters);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return AdvancedSubscriptionPlugin
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     * @return void
     */
    public function install()
    {
        $this->installDatabase();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->uninstallDatabase();
    }

    /**
     * Create the database tables for the plugin
     * @return void
     */
    private function installDatabase()
    {
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $pAdvSubMailTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $pAdvSubMailTypeTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $pAdvSubMailStatusTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);

        $sql = "CREATE TABLE IF NOT EXISTS $pAdvSubQueueTable (" .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "session_id varchar(255) NOT NULL, " .
            "user_id int UNSIGNED NOT NULL, " .
            "status int UNSIGNED NOT NULL, " .
            "created_at datetime NOT NULL, " .
            "updated_at datetime NULL, " .
            "PRIMARY KEY PK_tour_log (id)); ";

        $sql .= "CREATE TABLE $pAdvSubMailTypeTable ( " .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "description char(20), " .
            "PRIMARY KEY PK_advsub_mail_type (id) " .
            "); ";
        $sql .= "CREATE TABLE $pAdvSubMailTable ( " .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "message_id, mail_type_id, mail_status_id, " .
            "PRIMARY KEY PK_advsub_mail (id) " .
            "); ";

        $sql .= "CREATE TABLE $pAdvSubMailStatusTable ( " .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "description char(20), " .
            "PRIMARY KEY PK_advsub_mail_status (id) " .
            "); ";

        echo $sql;
        //Database::query($sql);
    }

    /**
     * Drop the database tables for the plugin
     * @return void
     */
    private function uninstallDatabase()
    {
        /* Drop plugin tables */
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $pAdvSubMailTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $pAdvSubMailTypeTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $pAdvSubMailStatusTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);

        $sql = "DROP TABLE IF EXISTS $pAdvSubQueueTable; ";
        $sql .= "DROP TABLE IF EXISTS $pAdvSubMailTable; ";
        $sql .= "DROP TABLE IF EXISTS $pAdvSubMailTypeTable; ";
        $sql .= "DROP TABLE IF EXISTS $pAdvSubMailStatusTable; ";

        Database::query($sql);

        /* Delete settings */
        $tSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        Database::query("DELETE FROM $tSettings WHERE subkey = 'advancedsubscription'");
    }

    /**
     * Return true if user is able to be added to queue for session subscription
     * @param int $userId
     * @param array $params MUST have keys:
     * "is_connected" Indicate if the user is online on external web
     * "profile_completed" Percentage of completed profile, given by WS
     * @throws Exception
     * @return bool
     */
    public function isAbleToRequest($userId, $params = array())
    {
        if (isset($params['is_connected']) && isset($params['profile_completed'])) {
            $isAble = false;
            $advSubPlugin = self::create();
            $wsUrl = $advSubPlugin->get('ws_url');
            // @TODO: Get connection status from user by WS
            $isConnected = $params['is_connected'];
            if ($isConnected) {
                $profileCompletedMin = $advSubPlugin->get('min_profile_percentage');
                // @TODO: Get completed profile percentage by WS
                $profileCompleted = (float) $params['profile_completed'];
                if ($profileCompleted > $profileCompletedMin) {
                    $checkInduction = $advSubPlugin->get('check_induction');
                    // @TODO: check if user have completed at least one induction session
                    $completedInduction = true;
                    if (!$checkInduction || $completedInduction) {
                        $uitMax = $advSubPlugin->get('yearly_cost_unit_converter');
                        $uitMax *= $advSubPlugin->get('yearly_cost_limit');
                        // @TODO: Get UIT completed by user this year by WS
                        $uitUser = 0;
                        if ($uitMax > $uitUser) {
                            $expendedTimeMax = $advSubPlugin->get('yearly_hours_limit');
                            // @TODO: Get Expended time from user data
                            $expendedTime = 0;
                            if ($expendedTimeMax > $expendedTime) {
                                $expendedNumMax = $advSubPlugin->get('courses_count_limit');
                                // @TODO: Get Expended num from user
                                $expendedNum = 0;
                                if ($expendedNumMax > $expendedNum) {
                                    $isAble = true;
                                } else {
                                    throw new \Exception(get_lang('AdvancedSubscriptionCourseXLimitReached'));
                                }
                            } else {
                                throw new \Exception(get_lang('AdvancedSubscriptionTimeXLimitReached'));
                            }
                        } else {
                            throw new \Exception(get_lang('AdvancedSubscriptionCostXLimitReached'));
                        }
                    } else {
                        throw new \Exception(get_lang('AdvancedSubscriptionIncompleteInduction'));
                    }
                } else {
                    throw new \Exception(get_lang('AdvancedSubscriptionProfileIncomplete'));
                }
            } else {
                throw new \Exception(get_lang('AdvancedSubscriptionNotConnected'));
            }

            return $isAble;
        } else {
            throw new \Exception($this->get_lang('AdvancedSubscriptionIncompleteParams'));
        }

    }

    /**
     * @param $userId
     * @param $sessionId
     */
    public function addToQueue($userId, $sessionId)
    {
        $now = api_get_utc_datetime();
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $sql = "INSERT INTO $pAdvSubQueueTable ( " .
            "session_id, user_id, status, created_at, updated_at " .
            ") VALUES ( " .
            "$sessionId, $userId, 0, $now, NULL ";
            "); ";

    }

    /**
     * @param $userId
     * @param $sessionId
     * @return bool|string
     */
    public function startSubscription($userId, $sessionId)
    {
        $result = false;
        $advSub = self::create();
        try {
            if ($advSub->isAbleToRequest($userId)) {
                $advSub->addToQueue($userId, $sessionId);
                $result = true;
            } else {
                throw new \Exception(get_lang('AdvancedSubscriptionNotMoreAble'));
            }
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        return $result;
    }

    /**
     * Check if session is open for subscription
     * @param $sessionId
     * @param string $fieldVariable
     * @return bool
     */
    public function isSessionOpen($sessionId, $fieldVariable = 'es_abierta')
    {
        $sessionId = (int) $sessionId;
        $fieldVariable = Database::escape_string($fieldVariable);
        $isOpen = false;
        if ($sessionId > 0 && !empty($fieldVariable)) {
            $sfTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
            $sfvTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
            $joinTable = $sfvTable . ' sfv INNER JOIN ' . $sfTable . ' sf ON sfv.field_id = sf.id ';
            $row = Database::select(
                'sfv.field_value as field_value',
                $joinTable,
                array(
                    'where' => array(
                        'sfv.session_id = ? AND ' => $sessionId,
                        'sf.field_variable = ?' => $fieldVariable,
                    )
                )
            );
            if (isset($row[0]) && is_array($row[0])) {
                $isOpen = (bool) $row[0]['field_value'];
            }
        }

        return $isOpen;
    }

    public function approvedByBoss()
    {

    }

    public function disapprovedByBoss()
    {

    }

    public function approvedByAdmin()
    {

    }

    public function disapprovedByAdmin()
    {

    }

    public function confirmTermsAndConditions()
    {

    }

    public function checkToken()
    {

    }

    public function sendMail()
    {

    }

    /**
     * Count the users in queue filtered by params (sessions, status)
     * @param array $params Input array containing the set of
     * session and status to count from queue
     * e.g:
     * array('sessions' => array(215, 218, 345, 502),
     * 'status' => array(0, 1, 2))
     * @return int
     */
    public function countQueueByParams($params)
    {
        $count = 0;
        if (!empty($params) && is_array($params)) {
            $advsubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
            $where['1 = ? '] = 1;
            if (isset($params['sessions']) && is_array($params['sessions'])) {
                $where['AND session_id IN ( ? ) '] = implode($params['sessions']);
            }
            if (isset($params['status']) && is_array($params['status'])) {
                $where['AND status IN ( ? ) '] = implode($params['status']);
            }
            $where['where'] = $where;
            $count = Database::select('COUNT(*)', $advsubQueueTable, $where);
            $count = $count[0];
        }
        return $count;
    }
}
