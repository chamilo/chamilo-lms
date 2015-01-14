<?php
/* For licensing terms, see /license.txt */
/**
 * @TODO: Improve description
 * This class is used to add an advanced subscription allowing the admin to
 * create user queues requesting a subscribe to a session
 * @package chamilo.plugin.advancedsubscription
 */


use PHP_Crypt\PHP_Crypt as PHP_Crypt;

class AdvancedSubscriptionPlugin extends Plugin implements HookPluginInterface
{
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
        $this->installHook();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->uninstallHook();
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
            "session_id int UNSIGNED NOT NULL, " .
            "user_id int UNSIGNED NOT NULL, " .
            "status int UNSIGNED NOT NULL, " .
            "last_message_id UNSIGNED NOT NULL, " .
            "created_at datetime NOT NULL, " .
            "updated_at datetime NULL, " .
            "PRIMARY KEY PK_tour_log (id)); ";
        Database::query($sql);

        $sql = "CREATE TABLE $pAdvSubMailTypeTable ( " .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "description char(20), " .
            "PRIMARY KEY PK_advsub_mail_type (id) " .
            "); ";

        Database::query($sql);
        $sql = "CREATE TABLE $pAdvSubMailTable ( " .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "message_id, mail_type_id, mail_status_id, " .
            "PRIMARY KEY PK_advsub_mail (id) " .
            "); ";
        Database::query($sql);

        $sql = "CREATE TABLE $pAdvSubMailStatusTable ( " .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "description char(20), " .
            "PRIMARY KEY PK_advsub_mail_status (id) " .
            "); ";
        Database::query($sql);
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
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $pAdvSubMailTable; ";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $pAdvSubMailTypeTable; ";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $pAdvSubMailStatusTable; ";
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
                $profileCompletedMin = (float) $advSubPlugin->get('min_profile_percentage');
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
     * @return bool|int
     */
    public function addToQueue($userId, $sessionId)
    {
        $now = api_get_utc_datetime();
        $pAdvSubQueueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $attributes = array(
            'session_id' => $sessionId,
            'user_id' => $userId,
            'status' => 0,
            'created_at' => $now,
            'updated_at' => null,
        );

        $id = Database::insert($pAdvSubQueueTable, $attributes);

        return $id;
    }

    /**
     * Register a message type
     * @param $description
     * @return bool|int
     */
    public function addMessageType($description)
    {
        $pAdvSubMessageTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_TYPE);
        $attributes = array(
            'description' => $description,
        );

        $id = Database::insert($pAdvSubMessageTable, $attributes);

        return $id;
    }

    /**
     * Register a message status
     * @param $description
     * @return bool|int
     */
    public function addMessageStatus($description)
    {
        $pAdvSubMessageTable = Database::get_main_table(TABLE_ADV_SUB_MAIL_STATUS);
        $attributes = array(
            'description' => $description,
        );

        $id = Database::insert($pAdvSubMessageTable, $attributes);

        return $id;
    }

    /**
     * Register message with type and status
     * @param $mailId
     * @param $mailTypeId
     * @param $mailStatusId
     * @return bool|int
     */
    public function addMessage($mailId, $mailTypeId, $mailStatusId)
    {
        $pAdvSubMessageTable = Database::get_main_table(TABLE_ADV_SUB_MAIL);
        $attributes = array(
            'message_id' => $mailId,
            'mail_type_id' => $mailTypeId,
            'mail_status_id' => $mailStatusId,
        );

        $id = Database::insert($pAdvSubMessageTable, $attributes);

        return $id;
    }

    /**
     * Check for requirements and register user into queue
     * @param $userId
     * @param $sessionId
     * @param $params
     * @return bool|string
     */
    public function startSubscription($userId, $sessionId, $params)
    {
        $result = false;
        if (isset($params['accept']) && !empty($sessionId) && !empty($userId)) {
            $advSub = self::create();
            try {
                if ($advSub->isAbleToRequest($userId, $params)) {
                    $result = (bool) $advSub->addToQueue($userId, $sessionId);
                } else {
                    throw new \Exception(get_lang('AdvancedSubscriptionNotMoreAble'));
                }
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
        } else {
            $result = 'Params not found';
        }

        return $result;
    }

    public function sendMailMessage($studentId, $subject, $content, $type = '')
    {
        global $_configuration; // @TODO: Add $_configuration['no_reply_user_id'] to configuration file

        $mailId = MessageManager::send_message(
            $studentId,
            $subject,
            $content,
            null,
            null,
            null,
            null,
            null,
            null,
            $_configuration['no_reply_user_id']
        );

        if (!empty($mailId)) {
            // Save as sent message
            $mailId = $this->addMessage($mailId, $type, ADV_SUB_MAIL_STATUS_MAIL_SENT);
        }

        return $mailId;
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

    public function updateQueueStatus($params, $newStatus)
    {
        if (isset($params['queue']['id'])) {
            $where = array(
                'id' => $params['queue']['id'],
            );
        } elseif(isset($params['user']['id']) && isset($params['session']['id'])) {
            $where = array(
                'user_id' => $params['user']['id'],
                'session_id' => $params['session']['id'],
            );
        }
        if (isset($where)) {
            $res = (bool) Database::update(
                Database::get_main_table(TABLE_ADV_SUB_QUEUE),
                array(
                    'unsigned' => $newStatus,
                ),
                $where
            );
        } else {
            $res = false;
        }

        return $res;
    }

    /**
     * Render and send mail by defined advanced subscription action
     * @param $data
     * @param $actionType
     * @return array
     */
    public function sendMail($data, $actionType)
    {
        $tpl = new Template($this->get_lang('plugin_title'));
        $tpl->assign('data', $data);
        $tplParams = array('user', 'student', 'students','superior', 'admin', 'session', 'signature', '_p', );
        foreach ($tplParams as $tplParam) {
            if (isset($data['superior'])) {
                $tpl->assign($tplParam, $data[$tplParam]);
            }
        }
        switch ($actionType) {
            case ADV_SUB_ACTION_STUDENT_REQUEST:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['student']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_received.tpl'),
                    ADV_SUB_MAIL_TYPE_A
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['superior']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior.tpl'),
                    ADV_SUB_MAIL_TYPE_B
                );
                break;
            case ADV_SUB_ACTION_SUPERIOR_APPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['student']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior_approved.tpl'),
                    ADV_SUB_MAIL_TYPE_A
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['superior']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approve_confirmed.tpl'),
                    ADV_SUB_MAIL_TYPE_B
                );
                // Mail to admin
                $mailIds[] = $this->sendMailMessage(
                    $data['admin']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_approved_info_admin.tpl'),
                    ADV_SUB_MAIL_TYPE_C
                );
                break;
            case ADV_SUB_ACTION_SUPERIOR_DISAPPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['student']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior_disapproved.tpl'),
                    ADV_SUB_MAIL_TYPE_A
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['superior']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_disapprove_confirmed.tpl'),
                    ADV_SUB_MAIL_TYPE_B
                );
                break;
            case ADV_SUB_ACTION_SUPERIOR_SELECT:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['student']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_received.tpl'),
                    ADV_SUB_MAIL_TYPE_A
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['superior']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior.tpl'),
                    ADV_SUB_MAIL_TYPE_B
                );
                break;
            case ADV_SUB_ACTION_ADMIN_APPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['student']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_accepted_notice_student.tpl'),
                    ADV_SUB_MAIL_TYPE_A
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['superior']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_accepted_notice_superior.tpl'),
                    ADV_SUB_MAIL_TYPE_B
                );
                // Mail to admin
                $mailIds[] = $this->sendMailMessage(
                    $data['admin']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_accepted_notice_admin.tpl'),
                    ADV_SUB_MAIL_TYPE_C
                );
                break;
            case ADV_SUB_ACTION_ADMIN_DISAPPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['student']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_rejected_notice_student.tpl'),
                    ADV_SUB_MAIL_TYPE_A
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['superior']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_rejected_notice_superior.tpl'),
                    ADV_SUB_MAIL_TYPE_B
                );
                // Mail to admin
                $mailIds[] = $this->sendMailMessage(
                    $data['admin']['id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_rejected_notice_admin.tpl'),
                    ADV_SUB_MAIL_TYPE_C
                );
                break;
            default:
                break;
        }

        return $mailIds;
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

    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin
     * @return int
     */
    public function installHook()
    {
        if (HookEvent::isHookPluginActive()) {
            $hookObserver = HookAdvancedSubscription::create();
            HookAdminBlock::create()->attach($hookObserver);
            HookWSRegistration::create()->attach($hookObserver);
        } else {
            // Hook management plugin is not enabled
        }
    }

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin
     * @return int
     */
    public function uninstallHook()
    {
        $hookObserver = HookAdvancedSubscription::create();
        HookAdminBlock::create()->detach($hookObserver);
        HookWSRegistration::create()->detach($hookObserver);
    }

    /**
     * Use AES-256 Encryption and url encoded
     * Return the message encrypted
     * @param mixed $data
     * @return string
     */
    public function encrypt($data)
    {
        global $_config;
        $key = sha1($_config['secret_key']);
        $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_256, PHP_Crypt::MODE_CTR);
        $encrypted = $crypt->createIV();
        $encrypted .= $crypt->encrypt(serialize($data));
        $encrypted = urlencode($encrypted);

        return $encrypted;
    }

    /**
     * Decrypt a message decoding as url an then decrypt AES-256 method
     * Return the message decrypted
     * @param $encrypted
     * @return mixed
     */
    public function decrypt($encrypted)
    {
        global $_config;
        $encrypted = urldecode($encrypted);
        $key = sha1($_config['secret_key']);
        $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_256, PHP_Crypt::MODE_CTR);
        $iv = substr($encrypted, 0, 16);
        $crypt->IV($iv);
        $data = unserialize($crypt->decrypt(substr($encrypted, 16)));

        return $data;
    }
}
