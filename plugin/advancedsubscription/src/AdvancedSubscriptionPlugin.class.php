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

        $sql = "CREATE TABLE IF NOT EXISTS $pAdvSubQueueTable (" .
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, " .
            "session_id int UNSIGNED NOT NULL, " .
            "user_id int UNSIGNED NOT NULL, " .
            "status int UNSIGNED NOT NULL, " .
            "last_message_id int UNSIGNED NOT NULL, " .
            "created_at datetime NOT NULL, " .
            "updated_at datetime NULL, " .
            "PRIMARY KEY PK_advsub_queue (id), " .
            "UNIQUE KEY UK_advsub_queue (user_id, session_id)); ";
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

        $sql = "DROP TABLE IF EXISTS $pAdvSubQueueTable; ";
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
                        $extra = new ExtraFieldValue('session');
                        $var = $extra->get_values_by_handler_and_field_variable($params['session_id'], 'cost');
                        $uitUser += $var['field_value'];
                        if ($uitMax >= $uitUser) {
                            $expendedTimeMax = $advSubPlugin->get('yearly_hours_limit');
                            // @TODO: Get Expended time from user data
                            $expendedTime = 0;
                            $var = $extra->get_values_by_handler_and_field_variable($params['session_id'], 'duration');
                            $expendedTime += $var['field_value'];
                            if ($expendedTimeMax >= $expendedTime) {
                                $expendedNumMax = $advSubPlugin->get('courses_count_limit');
                                // @TODO: Get Expended num from user
                                $expendedNum = 0;
                                if ($expendedNumMax >= $expendedNum) {
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
     * Register a user into a queue for a session
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
     * Register message with type and status
     * @param $mailId
     * @param $userId
     * @param $sessionId
     * @return bool|int
     */
    public function saveLastMessage($mailId, $userId, $sessionId)
    {
        $queueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $attributes = array(
            'last_message_id' => $mailId,
        );

        $num = Database::update(
            $queueTable,
            $attributes,
            array('user_id = ? AND session_id = ?' => array($userId, $sessionId))
        );

        return $num;
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
        if (!empty($sessionId) && !empty($userId)) {
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

    /**
     * Send message for the student subscription approval to a specific session
     * @param int $studentId
     * @param int $receiverId
     * @param string $subject
     * @param string $content
     * @param int $sessionId
     * @param bool $save
     * @return bool|int
     */
    public function sendMailMessage($studentId, $receiverId, $subject, $content, $sessionId, $save = false)
    {
        global $_configuration; // @TODO: Add $_configuration['no_reply_user_id'] to configuration file

        $mailId = MessageManager::send_message(
            $receiverId,
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

        if ($save && !empty($mailId)) {
            // Save as sent message
            $this->saveLastMessage($mailId, $studentId, $sessionId);
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
                        'sfv.session_id = ? AND sf.field_variable = ?' => array($sessionId, $fieldVariable),
                    )
                )
            );
            if (isset($row[0]) && is_array($row[0])) {
                $isOpen = (bool) $row[0]['field_value'];
            }
        }

        return $isOpen;
    }

    /**
     * Update the queue status for subscription approval rejected or accepted
     * @param $params
     * @param $newStatus
     * @return bool
     */
    public function updateQueueStatus($params, $newStatus)
    {
        if (isset($params['queue']['id'])) {
            $where = array(
                'id = ?' => $params['queue']['id'],
            );
        } elseif(isset($params['u']) && isset($params['s'])) {
            $where = array(
                'user_id = ? AND session_id = ?' => array($params['u'], $params['s']),
            );
        }
        if (isset($where)) {
            $res = (bool) Database::update(
                Database::get_main_table(TABLE_ADV_SUB_QUEUE),
                array(
                    'status' => $newStatus,
                    'updated_at' => api_get_utc_datetime(),
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
        $tplParams = array(
            'user',
            'student',
            'students',
            'superior',
            'admins',
            'session',
            'signature',
            'admin_view_url',
            'acceptUrl',
            'rejectUrl'
        );
        foreach ($tplParams as $tplParam) {
            $tpl->assign($tplParam, $data[$tplParam]);
        }
        $mailIds = array();
        switch ($actionType) {
            case ADV_SUB_ACTION_STUDENT_REQUEST:
                // Mail to student
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_received.tpl'),
                    $data['s']
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior.tpl'),
                    $data['s'],
                    true
                );
                break;
            case ADV_SUB_ACTION_SUPERIOR_APPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior_approved.tpl'),
                    $data['s']
                );
                // Mail to superior
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_approve_confirmed.tpl'),
                    $data['s']
                );
                // Mail to admin
                foreach ($data['admins'] as $adminId => $admin) {
                    $tpl->assign('admin', $admin);
                    $mailIds[] = $this->sendMailMessage(
                        $data['u'],
                        $adminId,
                        $this->get_lang('MailStudentRequest'),
                        $tpl->fetch('/advancedsubscription/views/advsub_request_approved_info_admin.tpl'),
                        $data['s'],
                        true
                    );
                }
                break;
            case ADV_SUB_ACTION_SUPERIOR_DISAPPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior_disapproved.tpl'),
                    $data['s'],
                    true
                );
                // Mail to superior
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_disapprove_confirmed.tpl'),
                    $data['s']
                );
                break;
            case ADV_SUB_ACTION_SUPERIOR_SELECT:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_received.tpl'),
                    $data['s']
                );
                // Mail to superior
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_superior.tpl'),
                    $data['s'],
                    true
                );
                break;
            case ADV_SUB_ACTION_ADMIN_APPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_accepted_notice_student.tpl'),
                    $data['s']
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_accepted_notice_superior.tpl'),
                    $data['s']
                );
                // Mail to admin
                $adminId = $data['current_user_id'];
                $tpl->assign('admin', $data['admins'][$adminId]);
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $adminId,
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_accepted_notice_admin.tpl'),
                    $data['s'],
                    true
                );
                break;
            case ADV_SUB_ACTION_ADMIN_DISAPPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_rejected_notice_student.tpl'),
                    $data['s'],
                    true
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['u'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_rejected_notice_superior.tpl'),
                    $data['s']
                );
                // Mail to admin
                $adminId = $data['current_user_id'];
                $tpl->assign('admin', $data['admins'][$adminId]);
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $adminId,
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_approval_admin_rejected_notice_admin.tpl'),
                    $data['s']
                );
                break;
            case ADV_SUB_ACTION_STUDENT_REQUEST_NO_BOSS:
                // Mail to student
                $mailIds['render'] = $this->sendMailMessage(
                    $data['u'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $tpl->fetch('/advancedsubscription/views/advsub_request_received.tpl'),
                    $data['s']
                );
                // Mail to admin
                foreach ($data['admins'] as $adminId => $admin) {
                    $tpl->assign('admin', $admin);
                    $mailIds[] = $this->sendMailMessage(
                        $data['u'],
                        $adminId,
                        $this->get_lang('MailStudentRequest'),
                        $tpl->fetch('/advancedsubscription/views/advsub_request_approved_info_admin.tpl'),
                        $data['s'],
                        true
                    );
                }
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
            $count = $count[0]['COUNT(*)'];
        }
        return $count;
    }

    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin
     * @return int
     */
    public function installHook()
    {
        $hookObserver = HookAdvancedSubscription::create();
        HookAdminBlock::create()->attach($hookObserver);
        HookWSRegistration::create()->attach($hookObserver);
        HookNotificationContent::create()->attach($hookObserver);
        HookNotificationTitle::create()->attach($hookObserver);
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
        HookNotificationContent::create()->detach($hookObserver);
        HookNotificationTitle::create()->detach($hookObserver);
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

    /**
     * Return the status from user in queue to session subscription
     * @param int $userId
     * @param int $sessionId
     * @return bool|int
     */
    public function getQueueStatus($userId, $sessionId)
    {
        if (!empty($userId) && !empty($sessionId)) {
            $queueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
            $row = Database::select(
                'status',
                $queueTable,
                array(
                    'where' => array(
                        'user_id = ? AND session_id = ?' => array($userId, $sessionId),
                    )
                )
            );

            if (count($row) == 1) {

                return $row[0]['status'];
            } else {

                return ADV_SUB_QUEUE_STATUS_NO_QUEUE;
            }
        }

        return false;
    }

    /**
     * Return the remaining vacancy
     * @param $sessionId
     * @return bool|int
     */
    public function getVacancy($sessionId)
    {
        if (!empty($sessionId)) {
            $extra = new ExtraFieldValue('session');
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'vacancies');
            $vacancy = intval($var['field_value']);
            if (!empty($vacancy)) {
                $vacancy -= $this->countQueueByParams(array('sessions' => $sessionId, 'status' => ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED));

                if ($vacancy >= 0) {

                    return $vacancy;
                }
            }
        }

        return false;
    }

    /**
     * Return the session details data from session extra field value
     * @param $sessionId
     * @return bool|mixed
     */
    public function getSessionDetails($sessionId)
    {
        if (!empty($sessionId)) {
            $extra = new ExtraFieldValue('session');
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'id');
            $data['id'] = $var['field_value'];
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'cost');
            $data['cost'] = $var['field_value'];
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'place');
            $data['place'] = $var['field_value'];
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'allow_visitors');
            $data['allow_visitors'] = $var['field_value'];
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'horas_lectivas');
            $data['duration'] = $var['field_value'];
            // Get brochure URL
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'brochure');
            $data['brochure'] = api_get_path(WEB_CODE_PATH) . $var['field_value'];
            // Get banner URL
            $var = $extra->get_values_by_handler_and_field_variable($sessionId, 'banner');
            $data['banner'] = api_get_path(WEB_CODE_PATH) . $var['field_value'];
            $data['description'] = SessionManager::getDescriptionFromSessionId($sessionId);

            return $data;
        }

        return false;
    }

    /**
     * Get status message
     * @param int $status
     * @param bool $isAble
     * @return string
     */
    public function getStatusMessage($status, $isAble = true)
    {
        $message = '';
        switch ($status)
        {
            case ADV_SUB_QUEUE_STATUS_NO_QUEUE:
                if ($isAble) {
                    $message = $this->get_lang('AdvancedSubscriptionNoQueueIsAble');
                } else {
                    $message = $this->get_lang('AdvancedSubscriptionNoQueue');
                }
                break;
            case ADV_SUB_QUEUE_STATUS_START:
                $message = $this->get_lang('AdvancedSubscriptionQueueStart');
                break;
            case ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueBossDisapproved');
                break;
            case ADV_SUB_QUEUE_STATUS_BOSS_APPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueBossApproved');
                break;
            case ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueAdminDisapproved');
                break;
            case ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueAdminApproved');
                break;
            default:
                $message = sprintf($this->get_lang('AdvancedSubscriptionQueueDefault'), $status);

        }

        return $message;
    }

    /**
     * Return the url to go to session
     * @param $sessionId
     * @return string
     */
    public function getSessionUrl($sessionId)
    {
        $url = api_get_path(WEB_CODE_PATH) . 'session/?session_id=' . $sessionId;
        return $url;
    }

    /**
     * Return the url to enter to subscription queue to session
     * @param $params
     * @return string
     */
    public function getQueueUrl($params)
    {
        $dataUrl['a'] = $params['a'];
        $dataUrl['s'] = intval($params['s']);
        $dataUrl['current_user_id'] = intval($params['current_user_id']);
        $dataUrl['u'] = intval($params['u']);
        $dataUrl['q'] = intval($params['q']);
        $dataUrl['e'] = intval($params['e']);

        $url = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/ajax/advsub.ajax.php?' .
            'a=' . $dataUrl['a'] . '&' .
            's=' . $dataUrl['s'] . '&' .
            'current_user_id=' . $dataUrl['current_user_id'] . '&' .
            'e=' . $dataUrl['e'] . '&' .
            'u=' . $dataUrl['u'] . '&' .
            'q=' . $dataUrl['q'] . '&' .
            'is_connected=' . $params['is_connected'] . '&' .
            'profile_completed=' . $params['profile_completed'] . '&' .
            'v=' . $this->generateHash($dataUrl);
        return $url;
    }

    /**
     * Return the list of student, in queue used by admin view
     * @param int $sessionId
     * @return array
     */
    public function listAllStudentsInQueueBySession($sessionId)
    {
        $extraSession = new ExtraFieldValue('session');
        $session = api_get_session_info($sessionId);
        $var = $extraSession->get_values_by_handler_and_field_variable($sessionId, 'target');
        $session['target'] = $var['field_value'];
        $var = $extraSession->get_values_by_handler_and_field_variable($sessionId, 'publication_end_date');
        $session['publication_end_date'] = $var['field_value'];
        $var = $extraSession->get_values_by_handler_and_field_variable($sessionId, 'mode');
        $session['mode'] = $var['field_value'];
        $var = $extraSession->get_values_by_handler_and_field_variable($sessionId, 'recommended_number_of_participants');
        $session['recommended_number_of_participants'] = $var['field_value'];
        $var = $extraSession->get_values_by_handler_and_field_variable($sessionId, 'vacancies');
        $session['vacancies'] = $var['field_value'];
        $queueTable = Database::get_main_table(TABLE_ADV_SUB_QUEUE);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $userJoinTable = $queueTable . ' q INNER JOIN ' . $userTable . ' u ON q.user_id = u.user_id';
        $where = array(
            'where' =>
            array(
                'q.session_id = ? AND q.status <> ? AND q.status <> ?' => array(
                    $sessionId,
                    ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED,
                    ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED,
                )
            )
        );
        $select = 'u.user_id, u.firstname, u.lastname, q.created_at, q.updated_at, q.status, q.id as queue_id';
        $students = Database::select($select, $userJoinTable, $where);
        foreach ($students as &$student) {
            $status = intval($student['status']);
            switch($status) {
                case ADV_SUB_QUEUE_STATUS_NO_QUEUE:
                case ADV_SUB_QUEUE_STATUS_START:
                    $student['validation'] = '';
                    break;
                case ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED:
                case ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED:
                    $student['validation'] = get_lang('No');
                    break;
                case ADV_SUB_QUEUE_STATUS_BOSS_APPROVED:
                case ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED:
                    $student['validation'] = get_lang('Yes');
                    break;
                default:
                    error_log(__FILE__ . ' ' . __FUNCTION__ . ' Student status no detected');
            }
        }
        $return = array(
            'session' => $session,
            'students' => $students,
        );

        return $return;

    }

    /**
     * List all session (id, name) for select input
     * @return array
     */
    public function listAllSessions()
    {
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $columns = 'id, name';
        return Database::select($columns, $sessionTable);
    }

    /**
     * Generate security hash to check data send by url params
     * @param string $data
     * @return string
     */
    public function generateHash($data)
    {
        global $_config;
        $key = sha1($_config['secret_key']);
        $data = serialize($data);
        return sha1($data . $key);
    }

    /**
     * Verify hash from data
     * @param string $data
     * @param string $hash
     * @return bool
     */
    public function checkHash($data, $hash)
    {
        return $this->generateHash($data) == $hash;
    }
}
