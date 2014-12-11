<?php
/* For licensing terms, see /license.txt */
/**
 * @TODO: Improve description
 * This class is used to add an advanced subscription allowing the admin to
 * create user queues requesting a subscribe to a session
 * @package chamilo.plugin.advanced_subscription
 */

class AdvancedSubscriptionPlugin extends Plugin
{
    /**
     * Constructor
     */
    function __construct()
    {
        $parameters = array(
            'uit_value' => 'boolean',
            'ws_url' => 'text',
            'min_profile_percentage' => 'text',
            'max_expended_uit' => 'text',
            'max_expended_num' => 'text',
            'max_course_times' => 'text',
            'check_induction' => 'boolean',
            'confirmation_message' => 'wysiwyg'
        );

        parent::__construct('1.0', 'Daniel Alejandro Barreto Alva', $parameters);
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
        $this->unistallDatabase();
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
    private function unistallDatabase()
    {
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $pAdvSubMailTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $pAdvSubMailTypeTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $pAdvSubMailStatusTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);

        $sql = "DROP TABLE IF EXISTS $pAdvSubQueueTable; ";
        $sql .= "DROP TABLE IF EXISTS $pAdvSubMailTable; ";
        $sql .= "DROP TABLE IF EXISTS $pAdvSubMailTypeTable; ";
        $sql .= "DROP TABLE IF EXISTS $pAdvSubMailStatusTable; ";

        Database::query($sql);
    }

    /**
     * Return true if user is able to be added to queue for session subscription
     * @param $userId
     * @throws Exception
     * @return bool
     */
    public function isAbleToRequest($userId)
    {
        $isAble = false;
        $advSubPlugin = self::create();
        $wsUrl = $advSubPlugin->get('ws_url');
        // @TODO: Get connection status from user by WS
        $isConnected = true;
        if ($isConnected) {
            $profileCompletedMin = $advSubPlugin->get('min_profile_percentage');
            // @TODO: Get completed profile percentage by WS
            $profileCompleted = 100.0;
            if ($profileCompleted > $profileCompletedMin) {
                $checkInduction = $advSubPlugin->get('check_induction');
                // @TODO: check if user have completed at least one induction session
                $completedInduction = true;
                if (!$checkInduction || $completedInduction) {
                    $uitMax = $advSubPlugin->get('uit_value');
                    $uitMax *= $advSubPlugin->get('max_expended_uit');
                    // @TODO: Get UIT completed by user this year by WS
                    $uitUser = 0;
                    if ($uitMax > $uitUser) {
                        $expendedTimeMax = $advSubPlugin->get('max_expended_time');
                        // @TODO: Get Expended time from user data
                        $expendedTime = 0;
                        if ($expendedTimeMax > $expendedTime) {
                            $expendedNumMax = $advSubPlugin->get('max_expended_num');
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
     * Check whether the tour should be displayed to the user
     * @param string $currentPageClass The class of the current page
     * @param int $userId The user id
     * @return boolean If the user has seen the tour return false, otherwise return true
     */
    public function checkTourForUser($currentPageClass, $userId)
    {
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $pAdvSubMailTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $pAdvSubMailTypeTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $pAdvSubMailStatusTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $checkResult = Database::select('count(1) as qty', $pluginTourLogTable, array(
            'where' => array(
                "page_class = '?' AND " => $currentPageClass,
                "user_id = ?" => intval($userId)
            )), 'first');

        if ($checkResult !== false) {
            if ($checkResult['qty'] > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the tour as seen
     * @param string $currentPageClass The class of the current page
     * @param int $userId The user id
     * @return void
     */
    public function saveCompletedTour($currentPageClass, $userId)
    {
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $pAdvSubMailTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $pAdvSubMailTypeTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $pAdvSubMailStatusTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        Database::insert($pluginTourLogTable, array(
            'page_class' => $currentPageClass,
            'user_id' => intval($userId),
            'visualization_datetime' => api_get_utc_datetime()
        ));
    }

    /**
     * Get the configuration to show the tour in pages
     * @return array The config data
     */
    public function getTourConfig()
    {
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $pAdvSubMailTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $pAdvSubMailTypeTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $pAdvSubMailStatusTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);
        $pluginPath = api_get_path(PLUGIN_PATH) . 'tour/';

        $jsonContent = file_get_contents($pluginPath . 'config/tour.json');

        $jsonData = json_decode($jsonContent, true);

        return $jsonData;
    }
}
