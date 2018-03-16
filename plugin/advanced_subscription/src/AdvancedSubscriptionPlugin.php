<?php
/* For licensing terms, see /license.txt */

/**
 * Class AdvancedSubscriptionPlugin
 * This class is used to add an advanced subscription allowing the admin to
 * create user queues requesting a subscribe to a session.
 *
 * @package chamilo.plugin.advanced_subscription
 */
class AdvancedSubscriptionPlugin extends Plugin implements HookPluginInterface
{
    protected $strings;
    private $errorMessages;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $parameters = [
            'yearly_cost_limit' => 'text',
            'yearly_hours_limit' => 'text',
            'yearly_cost_unit_converter' => 'text',
            'courses_count_limit' => 'text',
            'course_session_credit_year_start_date' => 'text',
            'ws_url' => 'text',
            'min_profile_percentage' => 'text',
            'check_induction' => 'boolean',
            'secret_key' => 'text',
            'terms_and_conditions' => 'wysiwyg',
        ];

        parent::__construct('1.0', 'Imanol Losada, Daniel Barreto', $parameters);
        $this->errorMessages = [];
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return AdvancedSubscriptionPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin.
     */
    public function install()
    {
        $this->installDatabase();
        $this->addAreaField();
        $this->installHook();
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        $setting = api_get_setting('advanced_subscription');
        if (!empty($setting)) {
            $this->uninstallHook();
            // Note: Keeping area field data is intended so it will not be removed
            $this->uninstallDatabase();
        }
    }

    /**
     * Get the error messages list.
     *
     * @return array The message list
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * Check if is allowed subscribe to open session.
     *
     * @param array $params WS params
     *
     * @return bool
     */
    public function isAllowedSubscribeToOpenSession($params)
    {
        $self = self::create();
        $wsUrl = $self->get('ws_url');
        $profileCompleted = 0;
        if (!empty($wsUrl)) {
            $client = new SoapClient(
                null,
                ['location' => $wsUrl, 'uri' => $wsUrl]
            );
            $userInfo = api_get_user_info(
                $params['user_id'],
                false,
                false,
                true
            );

            try {
                $profileCompleted = $client->__soapCall(
                    'getProfileCompletionPercentage',
                    $userInfo['extra']['drupal_user_id']
                );
            } catch (\Exception $e) {
                $profileCompleted = 0;
            }
        } elseif (isset($params['profile_completed'])) {
            $profileCompleted = (float) $params['profile_completed'];
        }
        $profileCompletedMin = (float) $self->get('min_profile_percentage');

        if ($profileCompleted < $profileCompletedMin) {
            $this->errorMessages[] = sprintf(
                $this->get_lang('AdvancedSubscriptionProfileIncomplete'),
                $profileCompletedMin,
                $profileCompleted
            );
        }

        $vacancy = $self->getVacancy($params['session_id']);
        $sessionInfo = api_get_session_info($params['session_id']);

        if ($sessionInfo['nbr_users'] >= $vacancy) {
            $this->errorMessages[] = sprintf(
                $this->get_lang('SessionXWithoutVacancies'),
                $sessionInfo['name']
            );
        }

        return empty($this->errorMessages);
    }

    /**
     * Return true if user is allowed to be added to queue for session subscription.
     *
     * @param int   $userId
     * @param array $params        MUST have keys:
     *                             "is_connected" Indicate if the user is online on external web
     *                             "profile_completed" Percentage of completed profile, given by WS
     * @param bool  $collectErrors Optional. Default is false. Whether collect all errors or throw exeptions
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isAllowedToDoRequest($userId, $params = [], $collectErrors = false)
    {
        $plugin = self::create();
        $wsUrl = $plugin->get('ws_url');
        // Student always is connected
        $isConnected = true;

        if (!$isConnected) {
            $this->errorMessages[] = $this->get_lang('AdvancedSubscriptionNotConnected');

            if (!$collectErrors) {
                throw new \Exception($this->get_lang('AdvancedSubscriptionNotConnected'));
            }
        }

        $profileCompletedMin = (float) $plugin->get('min_profile_percentage');
        $profileCompleted = 0;

        if (is_string($wsUrl) && !empty($wsUrl)) {
            $options = [
                'location' => $wsUrl,
                'uri' => $wsUrl,
            ];
            $client = new SoapClient(null, $options);
            $userInfo = api_get_user_info($userId);
            try {
                $profileCompleted = $client->__soapCall('getProfileCompletionPercentage', $userInfo['extra']['drupal_user_id']);
            } catch (\Exception $e) {
                $profileCompleted = 0;
            }
        } elseif (isset($params['profile_completed'])) {
            $profileCompleted = (float) $params['profile_completed'];
        }

        if ($profileCompleted < $profileCompletedMin) {
            $errorMessage = sprintf(
                $this->get_lang('AdvancedSubscriptionProfileIncomplete'),
                $profileCompletedMin,
                $profileCompleted
            );

            $this->errorMessages[] = $errorMessage;

            if (!$collectErrors) {
                throw new \Exception($errorMessage);
            }
        }

        $yearlyCostLimit = $plugin->get('yearly_cost_limit');
        $maxCost = $plugin->get('yearly_cost_unit_converter');
        $maxCost *= $yearlyCostLimit;
        $userCost = 0;
        $now = new DateTime(api_get_utc_datetime());
        $newYearDate = $plugin->get('course_session_credit_year_start_date');
        $newYearDate = !empty($newYearDate) ?
            new \DateTime($newYearDate.$now->format('/Y')) : $now;
        $extra = new ExtraFieldValue('session');
        $joinSessionTable = Database::get_main_table(TABLE_MAIN_SESSION_USER).' su INNER JOIN '.
            Database::get_main_table(TABLE_MAIN_SESSION).' s ON s.id = su.session_id';
        $whereSessionParams = 'su.relation_type = ? AND s.access_start_date >= ? AND su.user_id = ?';
        $whereSessionParamsValues = [
            0,
            $newYearDate->format('Y-m-d'),
            $userId,
        ];
        $whereSession = [
            'where' => [
                $whereSessionParams => $whereSessionParamsValues,
            ],
        ];
        $selectSession = 's.id AS id';
        $sessions = Database::select(
            $selectSession,
            $joinSessionTable,
            $whereSession
        );

        $expendedTimeMax = $plugin->get('yearly_hours_limit');
        $expendedTime = 0;

        if (is_array($sessions) && count($sessions) > 0) {
            foreach ($sessions as $session) {
                $costField = $extra->get_values_by_handler_and_field_variable($session['id'], 'cost');
                $userCost += $costField['value'];
                $teachingHoursField = $extra->get_values_by_handler_and_field_variable($session['id'], 'teaching_hours');
                $expendedTime += $teachingHoursField['value'];
            }
        }

        if (isset($params['sessionId'])) {
            $costField = $extra->get_values_by_handler_and_field_variable($params['sessionId'], 'cost');
            $userCost += $costField['value'];

            $teachingHoursField = $extra->get_values_by_handler_and_field_variable($params['sessionId'], 'teaching_hours');
            $expendedTime += $teachingHoursField['value'];
        }

        if ($maxCost <= $userCost) {
            $errorMessage = sprintf(
                $this->get_lang('AdvancedSubscriptionCostXLimitReached'),
                $yearlyCostLimit
            );

            $this->errorMessages[] = $errorMessage;

            if (!$collectErrors) {
                throw new \Exception($errorMessage);
            }
        }

        if ($expendedTimeMax <= $expendedTime) {
            $errorMessage = sprintf(
                $this->get_lang('AdvancedSubscriptionTimeXLimitReached'),
                $expendedTimeMax
            );

            $this->errorMessages[] = $errorMessage;

            if (!$collectErrors) {
                throw new \Exception($errorMessage);
            }
        }

        $expendedNumMax = $plugin->get('courses_count_limit');
        $expendedNum = count($sessions);

        if ($expendedNumMax <= $expendedNum) {
            $errorMessage = sprintf(
                $this->get_lang('AdvancedSubscriptionCourseXLimitReached'),
                $expendedNumMax
            );

            $this->errorMessages[] = $errorMessage;

            if (!$collectErrors) {
                throw new \Exception($errorMessage);
            }
        }

        $checkInduction = $plugin->get('check_induction');
        $numberOfApprovedInductionSessions = $this->getApprovedInductionSessions($userId);
        $completedInduction = $numberOfApprovedInductionSessions > 0;

        if ($checkInduction == 'true' && !$completedInduction) {
            $this->errorMessages[] = $this->get_lang('AdvancedSubscriptionIncompleteInduction');

            if (!$collectErrors) {
                throw new \Exception($this->get_lang('AdvancedSubscriptionIncompleteInduction'));
            }
        }

        return empty($this->errorMessages);
    }

    /**
     * Register a user into a queue for a session.
     *
     * @param $userId
     * @param $sessionId
     *
     * @return bool|int
     */
    public function addToQueue($userId, $sessionId)
    {
        // Filter input variables
        $userId = intval($userId);
        $sessionId = intval($sessionId);
        $now = api_get_utc_datetime();
        $advancedSubscriptionQueueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);
        $attributes = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'status' => 0,
            'created_at' => $now,
            'updated_at' => null,
        ];

        $id = Database::insert($advancedSubscriptionQueueTable, $attributes);

        return $id;
    }

    /**
     * Register message with type and status.
     *
     * @param $mailId
     * @param $userId
     * @param $sessionId
     *
     * @return bool|int
     */
    public function saveLastMessage($mailId, $userId, $sessionId)
    {
        // Filter variables
        $mailId = intval($mailId);
        $userId = intval($userId);
        $sessionId = intval($sessionId);
        $queueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);
        $attributes = [
            'last_message_id' => $mailId,
            'updated_at' => api_get_utc_datetime(),
        ];

        $num = Database::update(
            $queueTable,
            $attributes,
            ['user_id = ? AND session_id = ?' => [$userId, $sessionId]]
        );

        return $num;
    }

    /**
     * Check for requirements and register user into queue.
     *
     * @param $userId
     * @param $sessionId
     * @param $params
     *
     * @return bool|string
     */
    public function startSubscription($userId, $sessionId, $params)
    {
        $result = 'Params not found';
        if (!empty($sessionId) && !empty($userId)) {
            $plugin = self::create();
            try {
                if ($plugin->isAllowedToDoRequest($userId, $params)) {
                    $result = (bool) $plugin->addToQueue($userId, $sessionId);
                } else {
                    throw new \Exception($this->get_lang('AdvancedSubscriptionNotMoreAble'));
                }
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Send message for the student subscription approval to a specific session.
     *
     * @param int|array $studentId
     * @param int       $receiverId
     * @param string    $subject
     * @param string    $content
     * @param int       $sessionId
     * @param bool      $save
     * @param array     $fileAttachments
     *
     * @return bool|int
     */
    public function sendMailMessage(
        $studentId,
        $receiverId,
        $subject,
        $content,
        $sessionId,
        $save = false,
        $fileAttachments = []
    ) {
        if (!empty($fileAttachments) &&
            is_array($fileAttachments) &&
            isset($fileAttachments['files']) &&
            isset($fileAttachments['comments'])
        ) {
            $mailId = MessageManager::send_message(
                $receiverId,
                $subject,
                $content,
                $fileAttachments['files'],
                $fileAttachments['comments']
            );
        } else {
            $mailId = MessageManager::send_message(
                $receiverId,
                $subject,
                $content
            );
        }

        if ($save && !empty($mailId)) {
            // Save as sent message
            if (is_array($studentId) && !empty($studentId)) {
                foreach ($studentId as $student) {
                    $this->saveLastMessage($mailId, $student['user_id'], $sessionId);
                }
            } else {
                $studentId = intval($studentId);
                $this->saveLastMessage($mailId, $studentId, $sessionId);
            }
        } elseif (!empty($mailId)) {
            // Update queue row, updated_at
            Database::update(
                Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE),
                [
                    'updated_at' => api_get_utc_datetime(),
                ],
                [
                    'user_id = ? AND session_id = ?' => [$studentId, $sessionId],
                ]
            );
        }

        return $mailId;
    }

    /**
     * Check if session is open for subscription.
     *
     * @param $sessionId
     * @param string $fieldVariable
     *
     * @return bool
     */
    public function isSessionOpen($sessionId, $fieldVariable = 'is_open_session')
    {
        $extraFieldValue = new ExtraFieldValue('session');
        $result = $extraFieldValue->get_values_by_handler_and_field_variable(
            $sessionId,
            $fieldVariable
        );

        $isOpen = false;
        if (!empty($result)) {
            $isOpen = (bool) $result['value'];
        }

        return $isOpen;
    }

    /**
     * Check if user is in the session's target group based on its area.
     *
     * @param $userId
     * @param $sessionId
     * @param string $userFieldVariable
     * @param string $sessionFieldVariable
     *
     * @return bool
     */
    public function isUserInTargetGroup(
        $userId,
        $sessionId,
        $userFieldVariable = 'area',
        $sessionFieldVariable = 'target'
    ) {
        $extraSessionFieldValue = new ExtraFieldValue('session');
        $sessionTarget = $extraSessionFieldValue->get_values_by_handler_and_field_variable(
            $sessionId,
            $sessionFieldVariable
        );
        $extraUserFieldValue = new ExtraFieldValue('user');
        $userArea = $extraUserFieldValue->get_values_by_handler_and_field_variable(
            $userId,
            $userFieldVariable
        );
        $isInTargetGroup = false;
        if (isset($sessionTarget) && (!empty($sessionTarget)) && $sessionTarget['value'] == 'minedu') {
            if (substr($userArea['value'], 0, 6) == 'MINEDU') {
                $isInTargetGroup = true;
            }
        }
        if (isset($sessionTarget) && (!empty($sessionTarget)) && $sessionTarget['value'] == 'regiones') {
            if ((substr($userArea['value'], 0, 4) == 'UGEL') || (substr($userArea['value'], 0, 3) == 'DRE')) {
                $isInTargetGroup = true;
            }
        }

        return $isInTargetGroup;
    }

    /**
     * Update the queue status for subscription approval rejected or accepted.
     *
     * @param $params
     * @param $newStatus
     *
     * @return bool
     */
    public function updateQueueStatus($params, $newStatus)
    {
        $newStatus = intval($newStatus);
        $res = false;

        if (isset($params['queue']['id'])) {
            $where = [
                'id = ?' => intval($params['queue']['id']),
            ];
        } elseif (isset($params['studentUserId']) && isset($params['sessionId'])) {
            $where = [
                'user_id = ? AND session_id = ? AND status <> ? AND status <> ?' => [
                    intval($params['studentUserId']),
                    intval($params['sessionId']),
                    $newStatus,
                    ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED,
                ],
            ];
        }
        if (isset($where)) {
            $res = (bool) Database::update(
                Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE),
                [
                    'status' => $newStatus,
                    'updated_at' => api_get_utc_datetime(),
                ],
                $where
            );
        }

        return $res;
    }

    /**
     * Render and send mail by defined advanced subscription action.
     *
     * @param $data
     * @param $actionType
     *
     * @return array
     */
    public function sendMail($data, $actionType)
    {
        $template = new Template($this->get_lang('plugin_title'));
        $template->assign('data', $data);
        $templateParams = [
            'user',
            'student',
            'students',
            'superior',
            'admins',
            'session',
            'signature',
            'admin_view_url',
            'acceptUrl',
            'rejectUrl',
        ];
        foreach ($templateParams as $templateParam) {
            $template->assign($templateParam, $data[$templateParam]);
        }
        $mailIds = [];
        switch ($actionType) {
            case ADVANCED_SUBSCRIPTION_ACTION_STUDENT_REQUEST:
                // Mail to student
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $template->fetch('/advanced_subscription/views/student_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequest'),
                    $template->fetch('/advanced_subscription/views/student_notice_superior.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_SUPERIOR_APPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailBossAccept'),
                    $template->fetch('/advanced_subscription/views/superior_accepted_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                // Mail to superior
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailBossAccept'),
                    $template->fetch('/advanced_subscription/views/superior_accepted_notice_superior.tpl'),
                    $data['sessionId']
                );
                // Mail to admin
                foreach ($data['admins'] as $adminId => $admin) {
                    $template->assign('admin', $admin);
                    $mailIds[] = $this->sendMailMessage(
                        $data['studentUserId'],
                        $adminId,
                        $this->get_lang('MailBossAccept'),
                        $template->fetch('/advanced_subscription/views/superior_accepted_notice_admin.tpl'),
                        $data['sessionId']
                    );
                }
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_SUPERIOR_DISAPPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailBossReject'),
                    $template->fetch('/advanced_subscription/views/superior_rejected_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                // Mail to superior
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailBossReject'),
                    $template->fetch('/advanced_subscription/views/superior_rejected_notice_superior.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_SUPERIOR_SELECT:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequestSelect'),
                    $template->fetch('/advanced_subscription/views/student_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                // Mail to superior
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailStudentRequestSelect'),
                    $template->fetch('/advanced_subscription/views/student_notice_superior.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_ADMIN_APPROVE:
                $fileAttachments = [];
                if (api_get_plugin_setting('courselegal', 'tool_enable')) {
                    $courseLegal = CourseLegalPlugin::create();
                    $courses = SessionManager::get_course_list_by_session_id($data['sessionId']);
                    $course = current($courses);
                    $data['courseId'] = $course['id'];
                    $data['course'] = api_get_course_info_by_id($data['courseId']);
                    $termsAndConditions = $courseLegal->getData($data['courseId'], $data['sessionId']);
                    $termsAndConditions = $termsAndConditions['content'];
                    $termsAndConditions = $this->renderTemplateString($termsAndConditions, $data);
                    $tpl = new Template(get_lang('TermsAndConditions'));
                    $tpl->assign('session', $data['session']);
                    $tpl->assign('student', $data['student']);
                    $tpl->assign('sessionId', $data['sessionId']);
                    $tpl->assign('termsContent', $termsAndConditions);
                    $termsAndConditions = $tpl->fetch('/advanced_subscription/views/terms_and_conditions_to_pdf.tpl');
                    $pdf = new PDF();
                    $filename = 'terms'.sha1(rand(0, 99999));
                    $pdf->content_to_pdf($termsAndConditions, null, $filename, null, 'F');
                    $fileAttachments['file'][] = [
                        'name' => $filename.'.pdf',
                        'application/pdf' => $filename.'.pdf',
                        'tmp_name' => api_get_path(SYS_ARCHIVE_PATH).$filename.'.pdf',
                        'error' => UPLOAD_ERR_OK,
                        'size' => filesize(api_get_path(SYS_ARCHIVE_PATH).$filename.'.pdf'),
                    ];
                    $fileAttachments['comments'][] = get_lang('TermsAndConditions');
                }
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailAdminAccept'),
                    $template->fetch('/advanced_subscription/views/admin_accepted_notice_student.tpl'),
                    $data['sessionId'],
                    true,
                    $fileAttachments
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailAdminAccept'),
                    $template->fetch('/advanced_subscription/views/admin_accepted_notice_superior.tpl'),
                    $data['sessionId']
                );
                // Mail to admin
                $adminId = $data['currentUserId'];
                $template->assign('admin', $data['admins'][$adminId]);
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $adminId,
                    $this->get_lang('MailAdminAccept'),
                    $template->fetch('/advanced_subscription/views/admin_accepted_notice_admin.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_ADMIN_DISAPPROVE:
                // Mail to student
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailAdminReject'),
                    $template->fetch('/advanced_subscription/views/admin_rejected_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                // Mail to superior
                $mailIds[] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailAdminReject'),
                    $template->fetch('/advanced_subscription/views/admin_rejected_notice_superior.tpl'),
                    $data['sessionId']
                );
                // Mail to admin
                $adminId = $data['currentUserId'];
                $template->assign('admin', $data['admins'][$adminId]);
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $adminId,
                    $this->get_lang('MailAdminReject'),
                    $template->fetch('/advanced_subscription/views/admin_rejected_notice_admin.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_STUDENT_REQUEST_NO_BOSS:
                // Mail to student
                $mailIds['render'] = $this->sendMailMessage(
                    $data['studentUserId'],
                    $data['student']['user_id'],
                    $this->get_lang('MailStudentRequestNoBoss'),
                    $template->fetch('/advanced_subscription/views/student_no_superior_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                // Mail to admin
                foreach ($data['admins'] as $adminId => $admin) {
                    $template->assign('admin', $admin);
                    $mailIds[] = $this->sendMailMessage(
                        $data['studentUserId'],
                        $adminId,
                        $this->get_lang('MailStudentRequestNoBoss'),
                        $template->fetch('/advanced_subscription/views/student_no_superior_notice_admin.tpl'),
                        $data['sessionId']
                    );
                }
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_REMINDER_STUDENT:
                $mailIds['render'] = $this->sendMailMessage(
                    $data['student']['user_id'],
                    $data['student']['user_id'],
                    $this->get_lang('MailRemindStudent'),
                    $template->fetch('/advanced_subscription/views/reminder_notice_student.tpl'),
                    $data['sessionId'],
                    true
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_REMINDER_SUPERIOR:
                $mailIds['render'] = $this->sendMailMessage(
                    $data['students'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailRemindSuperior'),
                    $template->fetch('/advanced_subscription/views/reminder_notice_superior.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_REMINDER_SUPERIOR_MAX:
                $mailIds['render'] = $this->sendMailMessage(
                    $data['students'],
                    $data['superior']['user_id'],
                    $this->get_lang('MailRemindSuperior'),
                    $template->fetch('/advanced_subscription/views/reminder_notice_superior_max.tpl'),
                    $data['sessionId']
                );
                break;
            case ADVANCED_SUBSCRIPTION_ACTION_REMINDER_ADMIN:
                // Mail to admin
                foreach ($data['admins'] as $adminId => $admin) {
                    $template->assign('admin', $admin);
                    $mailIds[] = $this->sendMailMessage(
                        $data['students'],
                        $adminId,
                        $this->get_lang('MailRemindAdmin'),
                        $template->fetch('/advanced_subscription/views/reminder_notice_admin.tpl'),
                        $data['sessionId']
                    );
                }
                break;
            default:
                break;
        }

        return $mailIds;
    }

    /**
     * Count the users in queue filtered by params (sessions, status).
     *
     * @param array $params Input array containing the set of
     *                      session and status to count from queue
     *                      e.g:
     *                      array('sessions' => array(215, 218, 345, 502),
     *                      'status' => array(0, 1, 2))
     *
     * @return int
     */
    public function countQueueByParams($params)
    {
        $count = 0;
        if (!empty($params) && is_array($params)) {
            $advancedSubscriptionQueueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);
            $where['1 = ? '] = 1;
            if (isset($params['sessions']) && is_array($params['sessions'])) {
                foreach ($params['sessions'] as &$sessionId) {
                    $sessionId = intval($sessionId);
                }
                $where['AND session_id IN ( ? ) '] = implode($params['sessions']);
            }
            if (isset($params['status']) && is_array($params['status'])) {
                foreach ($params['status'] as &$status) {
                    $status = intval($status);
                }
                $where['AND status IN ( ? ) '] = implode($params['status']);
            }
            $where['where'] = $where;
            $count = Database::select('COUNT(*)', $advancedSubscriptionQueueTable, $where);
            $count = $count[0]['COUNT(*)'];
        }

        return $count;
    }

    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin.
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
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin.
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
     * Return the status from user in queue to session subscription.
     *
     * @param int $userId
     * @param int $sessionId
     *
     * @return bool|int
     */
    public function getQueueStatus($userId, $sessionId)
    {
        $userId = intval($userId);
        $sessionId = intval($sessionId);
        if (!empty($userId) && !empty($sessionId)) {
            $queueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);
            $row = Database::select(
                'status',
                $queueTable,
                [
                    'where' => [
                        'user_id = ? AND session_id = ?' => [$userId, $sessionId],
                    ],
                ]
            );

            if (count($row) == 1) {
                return $row[0]['status'];
            } else {
                return ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE;
            }
        }

        return false;
    }

    /**
     * Return the remaining vacancy.
     *
     * @param $sessionId
     *
     * @return bool|int
     */
    public function getVacancy($sessionId)
    {
        if (!empty($sessionId)) {
            $extra = new ExtraFieldValue('session');
            $var = $extra->get_values_by_handler_and_field_variable(
                $sessionId,
                'vacancies'
            );
            $vacancy = intval($var['value']);
            if (!empty($vacancy)) {
                $vacancy -= $this->countQueueByParams(
                    [
                        'sessions' => [$sessionId],
                        'status' => [ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED],
                    ]
                );
                if ($vacancy >= 0) {
                    return $vacancy;
                } else {
                    return 0;
                }
            }
        }

        return false;
    }

    /**
     * Return the session details data from a session ID (including the extra
     * fields used for the advanced subscription mechanism).
     *
     * @param $sessionId
     *
     * @return bool|mixed
     */
    public function getSessionDetails($sessionId)
    {
        if (!empty($sessionId)) {
            // Assign variables
            $fieldsArray = [
                'code',
                'cost',
                'place',
                'allow_visitors',
                'teaching_hours',
                'brochure',
                'banner',
            ];
            $extraField = new ExtraField('session');
            // Get session fields
            $fieldList = $extraField->get_all([
                'variable IN ( ?, ?, ?, ?, ?, ?, ? )' => $fieldsArray,
            ]);
            // Index session fields
            $fields = [];
            foreach ($fieldList as $field) {
                $fields[$field['id']] = $field['variable'];
            }

            $mergedArray = array_merge([$sessionId], array_keys($fields));

            $sql = "SELECT * FROM ".Database::get_main_table(TABLE_EXTRA_FIELD_VALUES)."
                    WHERE item_id = %d AND field_id IN (%d, %d, %d, %d, %d, %d, %d)";
            $sql = vsprintf($sql, $mergedArray);
            $sessionFieldValueList = Database::query($sql);
            while ($sessionFieldValue = Database::fetch_assoc($sessionFieldValueList)) {
                // Check if session field value is set in session field list
                if (isset($fields[$sessionFieldValue['field_id']])) {
                    $var = $fields[$sessionFieldValue['field_id']];
                    $val = $sessionFieldValue['value'];
                    // Assign session field value to session
                    $sessionArray[$var] = $val;
                }
            }
            $sessionArray['description'] = SessionManager::getDescriptionFromSessionId($sessionId);

            if (isset($sessionArray['brochure'])) {
                $sessionArray['brochure'] = api_get_path(WEB_UPLOAD_PATH).$sessionArray['brochure'];
            }
            if (isset($sessionArray['banner'])) {
                $sessionArray['banner'] = api_get_path(WEB_UPLOAD_PATH).$sessionArray['banner'];
            }

            return $sessionArray;
        }

        return false;
    }

    /**
     * Get status message.
     *
     * @param int  $status
     * @param bool $isAble
     *
     * @return string
     */
    public function getStatusMessage($status, $isAble = true)
    {
        switch ($status) {
            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE:
                $message = $this->get_lang('AdvancedSubscriptionNoQueue');
                if ($isAble) {
                    $message = $this->get_lang('AdvancedSubscriptionNoQueueIsAble');
                }
                break;
            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START:
                $message = $this->get_lang('AdvancedSubscriptionQueueStart');
                break;
            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_DISAPPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueBossDisapproved');
                break;
            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueBossApproved');
                break;
            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_DISAPPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueAdminDisapproved');
                break;
            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED:
                $message = $this->get_lang('AdvancedSubscriptionQueueAdminApproved');
                break;
            default:
                $message = sprintf($this->get_lang('AdvancedSubscriptionQueueDefault'), $status);
        }

        return $message;
    }

    /**
     * Return the url to go to session.
     *
     * @param $sessionId
     *
     * @return string
     */
    public function getSessionUrl($sessionId)
    {
        $url = api_get_path(WEB_CODE_PATH).'session/?session_id='.intval($sessionId);

        return $url;
    }

    /**
     * Get a url for subscribe a user in session.
     *
     * @param int   $userId The user ID
     * @param array $params Params from WS
     *
     * @return string
     */
    public function getOpenSessionUrl($userId, $params)
    {
        $userIsSubscribed = SessionManager::isUserSubscribedAsStudent(
            $params['session_id'],
            $userId
        );

        if ($userIsSubscribed) {
            return api_get_path(WEB_CODE_PATH)
                .'session/index.php?session_id='
                .intval($params['session_id']);
        }

        $params['secret_key'] = null;
        $params['user_id'] = null;
        $params['user_field'] = null;
        $params['is_connected'] = null;

        $urlParams = array_merge($params, ['user_id' => $userId]);

        $url = api_get_path(WEB_PLUGIN_PATH);
        $url .= 'advanced_subscription/src/open_session.php?';
        $url .= http_build_query($urlParams);

        return 'javascript:void(window.open(\''
            .$url
            .'\',\'AdvancedSubscriptionTerms\', \'toolbar=no,location=no,'
            .'status=no,menubar=no,scrollbars=yes,resizable=yes,width=700px,'
            .'height=600px\', \'100\' ))';
    }

    /**
     * Return the url to enter to subscription queue to session.
     *
     * @param $params
     *
     * @return string
     */
    public function getQueueUrl($params)
    {
        $url = api_get_path(WEB_PLUGIN_PATH).'advanced_subscription/ajax/advanced_subscription.ajax.php?'.
            'a='.Security::remove_XSS($params['action']).'&'.
            's='.intval($params['sessionId']).'&'.
            'current_user_id='.intval($params['currentUserId']).'&'.
            'e='.intval($params['newStatus']).'&'.
            'u='.intval($params['studentUserId']).'&'.
            'q='.intval($params['queueId']).'&'.
            'is_connected=1&'.
            'profile_completed='.intval($params['profile_completed']).'&'.
            'v='.$this->generateHash($params);

        return $url;
    }

    /**
     * Return the list of student, in queue used by admin view.
     *
     * @param int $sessionId
     *
     * @return array
     */
    public function listAllStudentsInQueueBySession($sessionId)
    {
        // Filter input variable
        $sessionId = intval($sessionId);
        // Assign variables
        $fieldsArray = [
            'target',
            'publication_end_date',
            'mode',
            'recommended_number_of_participants',
            'vacancies',
        ];
        $sessionArray = api_get_session_info($sessionId);
        $extraSession = new ExtraFieldValue('session');
        $extraField = new ExtraField('session');
        // Get session fields
        $fieldList = $extraField->get_all([
            'variable IN ( ?, ?, ?, ?, ?)' => $fieldsArray,
        ]);
        // Index session fields
        $fields = [];
        foreach ($fieldList as $field) {
            $fields[$field['id']] = $field['variable'];
        }

        $mergedArray = array_merge([$sessionId], array_keys($fields));
        $sessionFieldValueList = $extraSession->get_all(
            [
                'item_id = ? field_id IN ( ?, ?, ?, ?, ?, ?, ? )' => $mergedArray,
            ]
        );
        foreach ($sessionFieldValueList as $sessionFieldValue) {
            // Check if session field value is set in session field list
            if (isset($fields[$sessionFieldValue['field_id']])) {
                $var = $fields[$sessionFieldValue['field_id']];
                $val = $sessionFieldValue['value'];
                // Assign session field value to session
                $sessionArray[$var] = $val;
            }
        }
        $queueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $userJoinTable = $queueTable.' q INNER JOIN '.$userTable.' u ON q.user_id = u.user_id';
        $where = [
            'where' => [
                'q.session_id = ?' => [
                    $sessionId,
                ],
            ],
            'order' => 'q.status DESC, u.lastname ASC',
        ];
        $select = 'u.user_id, u.firstname, u.lastname, q.created_at, q.updated_at, q.status, q.id as queue_id';
        $students = Database::select($select, $userJoinTable, $where);
        foreach ($students as &$student) {
            $status = intval($student['status']);
            switch ($status) {
                case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE:
                case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START:
                    $student['validation'] = '';
                    break;
                case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_DISAPPROVED:
                case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_DISAPPROVED:
                    $student['validation'] = 'No';
                    break;
                case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED:
                case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED:
                    $student['validation'] = 'Yes';
                    break;
                default:
                    error_log(__FILE__.' '.__FUNCTION__.' Student status no detected');
            }
        }
        $return = [
            'session' => $sessionArray,
            'students' => $students,
        ];

        return $return;
    }

    /**
     * List all session (id, name) for select input.
     *
     * @param int $limit
     *
     * @return array
     */
    public function listAllSessions($limit = 100)
    {
        $limit = intval($limit);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $columns = 'id, name';
        $conditions = [];
        if ($limit > 0) {
            $conditions = [
                'order' => 'name',
                'limit' => $limit,
            ];
        }

        return Database::select($columns, $sessionTable, $conditions);
    }

    /**
     * Generate security hash to check data send by url params.
     *
     * @param string $data
     *
     * @return string
     */
    public function generateHash($data)
    {
        $key = sha1($this->get('secret_key'));
        // Prepare array to have specific type variables
        $dataPrepared['action'] = strval($data['action']);
        $dataPrepared['sessionId'] = intval($data['sessionId']);
        $dataPrepared['currentUserId'] = intval($data['currentUserId']);
        $dataPrepared['studentUserId'] = intval($data['studentUserId']);
        $dataPrepared['queueId'] = intval($data['queueId']);
        $dataPrepared['newStatus'] = intval($data['newStatus']);
        $dataPrepared = serialize($dataPrepared);

        return sha1($dataPrepared.$key);
    }

    /**
     * Verify hash from data.
     *
     * @param string $data
     * @param string $hash
     *
     * @return bool
     */
    public function checkHash($data, $hash)
    {
        return $this->generateHash($data) == $hash;
    }

    /**
     * Copied and fixed from plugin.class.php
     * Returns the "system" name of the plugin in lowercase letters.
     *
     * @return string
     */
    public function get_name()
    {
        return 'advanced_subscription';
    }

    /**
     * Return the url to show subscription terms.
     *
     * @param array $params
     * @param int   $mode
     *
     * @return string
     */
    public function getTermsUrl($params, $mode = ADVANCED_SUBSCRIPTION_TERMS_MODE_POPUP)
    {
        $urlParams = [
            'a' => Security::remove_XSS($params['action']),
            's' => intval($params['sessionId']),
            'current_user_id' => intval($params['currentUserId']),
            'e' => intval($params['newStatus']),
            'u' => intval($params['studentUserId']),
            'q' => intval($params['queueId']),
            'is_connected' => 1,
            'profile_completed' => intval($params['profile_completed']),
            'v' => $this->generateHash($params),
        ];

        switch ($mode) {
            case ADVANCED_SUBSCRIPTION_TERMS_MODE_POPUP:
            case ADVANCED_SUBSCRIPTION_TERMS_MODE_FINAL:
                $urlParams['r'] = 0;
                break;
            case ADVANCED_SUBSCRIPTION_TERMS_MODE_REJECT:
                $urlParams['r'] = 1;
                break;
        }

        $url = api_get_path(WEB_PLUGIN_PATH)."advanced_subscription/src/terms_and_conditions.php?";
        $url .= http_build_query($urlParams);

        // Launch popup
        if ($mode == ADVANCED_SUBSCRIPTION_TERMS_MODE_POPUP) {
            $url = 'javascript:void(window.open(\''.$url.'\',\'AdvancedSubscriptionTerms\', \'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700px,height=600px\', \'100\' ))';
        }

        return $url;
    }

    /**
     * Return the url to get mail rendered.
     *
     * @param array $params
     *
     * @return string
     */
    public function getRenderMailUrl($params)
    {
        $url = api_get_path(WEB_PLUGIN_PATH).'advanced_subscription/src/render_mail.php?'.
            'q='.$params['queueId'].'&'.
            'v='.$this->generateHash($params);

        return $url;
    }

    /**
     * Return the last message id from queue row.
     *
     * @param int $studentUserId
     * @param int $sessionId
     *
     * @return int|bool
     */
    public function getLastMessageId($studentUserId, $sessionId)
    {
        $studentUserId = intval($studentUserId);
        $sessionId = intval($sessionId);
        if (!empty($sessionId) && !empty($studentUserId)) {
            $row = Database::select(
                'last_message_id',
                Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE),
                [
                    'where' => [
                        'user_id = ? AND session_id = ?' => [$studentUserId, $sessionId],
                    ],
                ]
            );

            if (count($row) > 0) {
                return $row[0]['last_message_id'];
            }
        }

        return false;
    }

    /**
     * Return string replacing tags "{{}}"with variables assigned in $data.
     *
     * @param string $templateContent
     * @param array  $data
     *
     * @return string
     */
    public function renderTemplateString($templateContent, $data = [])
    {
        $twigString = new \Twig_Environment(new \Twig_Loader_String());
        $templateContent = $twigString->render(
            $templateContent,
            $data
        );

        return $templateContent;
    }

    /**
     * addAreaField() (adds an area field if it is not already created).
     */
    private function addAreaField()
    {
        $extraField = new ExtraField('user');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable('area');
        $areaExists = $extraFieldHandler !== false;

        if (!$areaExists) {
            $extraField = new ExtraField('user');
            $extraField->save([
                'field_type' => 1,
                'variable' => 'area',
                'display_text' => get_plugin_lang('Area', 'AdvancedSubscriptionPlugin'),
                'default_value' => null,
                'field_order' => null,
                'visible_to_self' => 1,
                'changeable' => 1,
                'filter' => null,
            ]);
        }
    }

    /**
     * Create the database tables for the plugin.
     */
    private function installDatabase()
    {
        $advancedSubscriptionQueueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);

        $sql = "CREATE TABLE IF NOT EXISTS $advancedSubscriptionQueueTable (".
            "id int UNSIGNED NOT NULL AUTO_INCREMENT, ".
            "session_id int UNSIGNED NOT NULL, ".
            "user_id int UNSIGNED NOT NULL, ".
            "status int UNSIGNED NOT NULL, ".
            "last_message_id int UNSIGNED NOT NULL, ".
            "created_at datetime NOT NULL, ".
            "updated_at datetime NULL, ".
            "PRIMARY KEY PK_advanced_subscription_queue (id), ".
            "UNIQUE KEY UK_advanced_subscription_queue (user_id, session_id)); ";
        Database::query($sql);
    }

    /**
     * Drop the database tables for the plugin.
     */
    private function uninstallDatabase()
    {
        /* Drop plugin tables */
        $advancedSubscriptionQueueTable = Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE);
        $sql = "DROP TABLE IF EXISTS $advancedSubscriptionQueueTable; ";
        Database::query($sql);

        /* Delete settings */
        $settingsTable = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        Database::query("DELETE FROM $settingsTable WHERE subkey = 'advanced_subscription'");
    }

    /**
     * Get the count of approved induction sessions by a user.
     *
     * @param int $userId The user id
     *
     * @return int The count of approved sessions
     */
    private function getApprovedInductionSessions($userId)
    {
        $tSession = Database::get_main_table(TABLE_MAIN_SESSION);
        $tSessionField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $tSessionFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $extraFieldType = \Chamilo\CoreBundle\Entity\ExtraField::SESSION_FIELD_TYPE;
        $sql = "SELECT s.id FROM $tSession AS s
            INNER JOIN $tSessionFieldValues AS sfv ON s.id = sfv.item_id
            INNER JOIN $tSessionField AS sf ON sfv.field_id = sf.id
            INNER JOIN $tSessionUser AS su ON s.id = su.session_id
            WHERE
                sf.extra_field_type = $extraFieldType AND
                sf.variable = 'is_induction_session' AND
                su.relation_type = 0 AND
                su.user_id = ".intval($userId);

        $result = Database::query($sql);

        if ($result === false) {
            return 0;
        }

        $numberOfApproved = 0;

        while ($session = Database::fetch_assoc($result)) {
            $numberOfApprovedCourses = 0;
            $courses = SessionManager::get_course_list_by_session_id($session['id']);

            foreach ($courses as $course) {
                $courseCategories = Category::load(
                    null,
                    null,
                    $course['code'],
                    null,
                    null,
                    $session['id'],
                    false
                );

                if (count($courseCategories) > 0 &&
                    Category::userFinishedCourse($userId, $courseCategories[0])
                ) {
                    $numberOfApprovedCourses++;
                }
            }

            if ($numberOfApprovedCourses === count($courses)) {
                $numberOfApproved++;
            }
        }

        return $numberOfApproved;
    }
}
