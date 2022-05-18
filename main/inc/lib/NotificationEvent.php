<?php
/* For licensing terms, see /license.txt */

class NotificationEvent extends Model
{
    public const ACCOUNT_EXPIRATION = 1;
    public const JUSTIFICATION_EXPIRATION = 2;
    public const GLOBAL_NOTIFICATION = 3;
    public const SPECIFIC_USER = 4;

    public $table;
    public $columns = [
        'id',
        'title',
        'content',
        'link',
        'persistent',
        'day_diff',
        'event_type',
        'event_id',
    ];
    public $extraFieldName;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = 'notification_event';
        $this->extraFieldName = 'notification_event';
    }

    public function eventTypeToString($eventTypeId)
    {
        $list = $this->getEventsForSelect(false);

        return $list[$eventTypeId];
    }

    public function getEventsForSelect($onlyEnabled = true): array
    {
        $eventTypes = [
            self::ACCOUNT_EXPIRATION => get_lang('AccountExpiration'),
            self::GLOBAL_NOTIFICATION => get_lang('Global'),
            self::SPECIFIC_USER => get_lang('SpecificUsers'),
        ];

        if (!$onlyEnabled || api_get_plugin_setting('justification', 'tool_enable') === 'true') {
            $eventTypes[self::JUSTIFICATION_EXPIRATION] = get_lang('JustificationExpiration');
        }

        return $eventTypes;
    }

    /**
     * @throws Exception
     */
    public function getForm(FormValidator $form, $data = []): FormValidator
    {
        $options = $this->getEventsForSelect();
        $form->addSelect('event_type', get_lang('EventType'), $options);
        $form->freeze('event_type');

        $eventType = $data['event_type'];
        switch ($eventType) {
            case self::JUSTIFICATION_EXPIRATION:
                $list = [];
                if (api_get_plugin_setting('justification', 'tool_enable') === 'true'
                    && $list = Justification::create()->getList()
                ) {
                    $list = array_column($list, 'name', 'id');
                }
                $form->addSelect('event_id', get_lang('JustificationType'), $list);
                $form->freeze('event_id');

                break;
            default:
                break;
        }

        $form->addText('title', get_lang('Title'));
        $form->addTextarea('content', get_lang('Content'));
        $form->addText('link', get_lang('Link'), false);
        $form->addCheckBox('persistent', get_lang('Persistent'));
        $form->addNumeric('day_diff', get_lang('DaysDifference'), false);

        switch ($eventType) {
            case self::SPECIFIC_USER:
                $form->addSelectAjax(
                    'users',
                    get_lang('Users'),
                    $data['users'] ?? [],
                    [
                        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
                        'multiple' => 'multiple',
                    ]
                );
                //no break
            case self::GLOBAL_NOTIFICATION:
                $form->removeElement('day_diff');
                break;
        }

        return $form;
    }

    /**
     * @throws Exception
     */
    public function getAddForm(FormValidator $form): FormValidator
    {
        $options = $this->getEventsForSelect();
        $eventType = $form->getSubmitValue('event_type');

        $form->addSelect(
            'event_type',
            get_lang('EventType'),
            $options,
            ['placeholder' => get_lang('SelectAnOption'), 'onchange' => 'document.add.submit()']
        );

        if (!empty($eventType)) {
            $form->freeze('event_type');
            $form->addText('title', get_lang('Title'));
            $form->addTextarea('content', get_lang('Content'));
            $form->addText('link', get_lang('Link'), false);
            $form->addCheckBox('persistent', get_lang('Persistent'));
            $form->addNumeric('day_diff', get_lang('DaysDifference'), false);

            switch ($eventType) {
                case self::JUSTIFICATION_EXPIRATION:
                    $list = [];
                    if (api_get_plugin_setting('justification', 'tool_enable') === 'true'
                        && $list = Justification::create()->getList()
                    ) {
                        $list = array_column($list, 'name', 'id');
                    }
                    $form->addSelect('event_id', get_lang('JustificationType'), $list);
                    break;
                case self::SPECIFIC_USER:
                    $form->addSelectAjax(
                        'users',
                        get_lang('Users'),
                        [],
                        [
                            'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
                            'multiple' => 'multiple',
                        ]
                    );
                    //no break
                case self::GLOBAL_NOTIFICATION:
                    $form->removeElement('day_diff');
                    break;
                default:
                    break;
            }
            $form->addButtonSave(get_lang('Save'));
        }

        return $form;
    }

    public function getUserExtraData($userId)
    {
        $data = UserManager::get_extra_user_data_by_field($userId, $this->extraFieldName);

        return $data['notification_event'] ?? '';
    }

    /**
     * @throws Exception
     */
    public function getNotificationsByUser(int $userId): array
    {
        $userInfo = api_get_user_info($userId);
        $events = $this->get_all();
        $extraFieldData = $this->getUserExtraData($userId);

        $notifications = [];
        foreach ($events as $event) {
            $days = (int) $event['day_diff'];
            $checkIsRead = $event['persistent'] == 0;
            $eventItemId = $event['event_id'];

            switch ($event['event_type']) {
                case self::ACCOUNT_EXPIRATION:
                    if (empty($userInfo['expiration_date'])) {
                        break;
                    }

                    $id = 'id_'.self::ACCOUNT_EXPIRATION.'_event_'.$event['id'].'_'.$userInfo['id'];

                    $read = false;
                    if ($checkIsRead) {
                        $read = $this->isRead($id, $extraFieldData);
                    }

                    $showNotification = $this->showNotification($userInfo['expiration_date'], $days);
                    if ($showNotification && $read === false) {
                        $notifications[] = [
                            'id' => $id,
                            'title' => $event['title'],
                            'content' => $event['content'],
                            'event_text' => get_lang('ExpirationDate').': '.api_get_local_time($userInfo['expiration_date']),
                            'link' => $event['link'],
                            'persistent' => $event['persistent'],
                        ];
                    }
                    break;
                case self::JUSTIFICATION_EXPIRATION:
                    if (api_get_plugin_setting('justification', 'tool_enable') !== 'true') {
                        break;
                    }

                    $plugin = Justification::create();
                    $userJustificationList = $plugin->getUserJustificationList($userId);

                    foreach ($userJustificationList as $userJustification) {
                        if (empty($userJustification['date_validity'])) {
                            continue;
                        }

                        if ($eventItemId != $userJustification['justification_document_id']) {
                            continue;
                        }

                        $showNotification = $this->showNotification($userJustification['date_validity'], $days);

                        $id = 'id_'.self::JUSTIFICATION_EXPIRATION.'_event_'.$event['id'].'_'.$userJustification['id'];

                        $fieldData = $plugin->getJustification($userJustification['justification_document_id']);

                        $read = false;
                        if ($checkIsRead) {
                            $read = $this->isRead($id, $extraFieldData);
                        }

                        $eventText = $plugin->get_lang('Justification').': '.$fieldData['name'].' <br />';
                        $eventText .= $plugin->get_lang('JustificationDate').': '.$userJustification['date_validity'];

                        $url = $event['link'];
                        if (empty($url)) {
                            $url = api_get_path(WEB_CODE_PATH).'auth/justification.php#'.$fieldData['code'];
                        }

                        if ($showNotification && $read === false) {
                            $notifications[] = [
                                'id' => $id,
                                'title' => $event['title'],
                                'content' => $event['content'],
                                'event_text' => $eventText,
                                'link' => $url,
                                'persistent' => $event['persistent'],
                            ];
                        }
                    }
                    break;
                case self::SPECIFIC_USER:
                    $assignedUsers = self::getAssignedUsers($event['id']);
                    $assignedUserIdList = array_keys($assignedUsers);

                    if (!in_array($userId, $assignedUserIdList)) {
                        break;
                    }
                    //no break
                case self::GLOBAL_NOTIFICATION:
                    $id = "id_{$event['event_type']}_event_{$event['id']}_$userId";

                    $wasRead = $checkIsRead && $this->isRead($id, $extraFieldData);

                    if (!$wasRead) {
                        $notifications[] = [
                            'id' => $id,
                            'title' => $event['title'],
                            'content' => $event['content'],
                            'event_text' => null,
                            'link' => $event['link'],
                            'persistent' => $event['persistent'],
                        ];
                    }
                    break;
            }
        }

        return $notifications;
    }

    public function isRead($id, $extraData): bool
    {
        $userId = api_get_user_id();

        if (empty($extraData)) {
            return false;
        }

        $data = $this->getUserExtraData($userId);
        if (empty($data)) {
            return false;
        }

        $data = json_decode($data);

        if (in_array($id, $data)) {
            return true;
        }

        return false;
    }

    public function markAsRead($id): bool
    {
        if (empty($id)) {
            return false;
        }
        $userId = api_get_user_id();
        $data = $this->getUserExtraData($userId);
        if (!empty($data)) {
            $data = json_decode($data);
        } else {
            $data = [];
        }
        $data[] = $id;
        $data = json_encode($data);

        UserManager::update_extra_field_value($userId, $this->extraFieldName, $data);

        return true;
    }

    /**
     * @throws Exception
     */
    public function showNotification($date, $dayDiff): bool
    {
        $today = api_get_utc_datetime();
        $expiration = api_get_utc_datetime($date, false, true);
        $interval = new DateInterval('P'.$dayDiff.'D');
        $diff = $expiration->sub($interval);

        if ($diff->format('Y-m-d H:i:s') < $today) {
            return true;
        }

        return false;
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notification_event (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            title VARCHAR(255),
            content TEXT,
            link TEXT,
            persistent INT,
            day_diff INT,
            event_type VARCHAR(255)
        )";
        Database::query($sql);
    }

    public function save($params, $show_query = false)
    {
        $userIdList = [];

        if (isset($params['users'])) {
            $userIdList = $params['users'];
            unset($params['users']);
        }

        /** @var int|bool $saved */
        $saved = parent::save($params, $show_query);

        if (false !== $saved && !empty($userIdList)) {
            self::assignUserIdList($saved, $userIdList);
        }

        return $saved;
    }

    public function update($params, $showQuery = false): bool
    {
        $userIdList = [];

        if (isset($params['users'])) {
            $userIdList = $params['users'];
            unset($params['users']);
        }

        $updated = parent::update($params, $showQuery);

        self::deleteAssignedUsers($params['id']);
        self::assignUserIdList($params['id'], $userIdList);

        return $updated;
    }

    public function get($id)
    {
        $props = parent::get($id);
        $props['users'] = self::getAssignedUsers($id);

        return $props;
    }

    public static function assignUserIdList(int $eventId, array $userIdList)
    {
        foreach ($userIdList as $userId) {
            Database::insert(
                'notification_event_rel_user',
                [
                    'event_id' => $eventId,
                    'user_id' => (int) $userId,
                ]
            );
        }
    }

    public static function getAssignedUsers(int $eventId): array
    {
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);

        $result = Database::select(
            'u.id, u.username, u.firstname, u.lastname',
            "notification_event_rel_user neru INNER JOIN $tblUser u ON neru.user_id = u.id",
            ['where' => ['neru.event_id = ?' => $eventId]]
        );

        $userList = [];

        foreach ($result as $userInfo) {
            $userList[$userInfo['id']] = api_get_person_name(
                $userInfo['firstname'],
                $userInfo['lastname'],
                null,
                null,
                null,
                $userInfo['username']
            );
        }

        return $userList;
    }

    public static function deleteAssignedUsers(int $eventId)
    {
        Database::delete(
            'notification_event_rel_user',
            ['event_id = ?' => $eventId]
        );
    }
}
