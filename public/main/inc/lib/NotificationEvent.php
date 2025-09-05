<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

class NotificationEvent extends Model
{
    const ACCOUNT_EXPIRATION = 1;
    const JUSTIFICATION_EXPIRATION = 2;
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
    public string $extraFieldName;

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
        $list = $this->getEventsForSelect();

        return $list[$eventTypeId];
    }

    public function getEventsForSelect()
    {
        return [
            self::ACCOUNT_EXPIRATION => get_lang('Account expiration'),
            self::JUSTIFICATION_EXPIRATION => get_lang('Justification expiration'),
        ];
    }

    /**
     * Get a prepared FormValidator form (passed as first param) with the justification fields
     * @param FormValidator $form
     * @param array $data
     * @return FormValidator
     * @throws Exception
     */
    public function getForm(FormValidator $form, array $data = []): FormValidator
    {
        $options = $this->getEventsForSelect();
        $form->addSelect('event_type', get_lang('Event type'), $options);
        $form->freeze('event_type');

        $eventType = $data['event_type'];
        switch ($eventType) {
            case self::JUSTIFICATION_EXPIRATION:
                $plugin = Justification::create();
                $list = $plugin->getList();
                $list = array_column($list, 'name', 'id');
                $form->addSelect('event_id', get_lang('Justification type'), $list);
                $form->freeze('event_id');

                break;
            default:
                break;
        }

        $form->addText('title', get_lang('Title'));
        $form->addTextarea('content', get_lang('Content'));
        $form->addText('link', get_lang('Link'), false);
        $form->addCheckBox('persistent', get_lang('Persistent'));
        $form->addNumeric('day_diff', get_lang('DayDiff'), false);

        return $form;
    }

    /**
     * Get addition form for the justification notification
     * @param FormValidator $form
     * @return FormValidator
     * @throws Exception
     */
    public function getAddForm(FormValidator $form): FormValidator
    {
        $options = $this->getEventsForSelect();
        $eventType = $form->getSubmitValue('event_type');

        $form->addSelect(
            'event_type',
            get_lang('Event type'),
            $options,
            ['placeholder' => get_lang('Please select an option'), 'onchange' => 'document.add.submit()']
        );

        if (!empty($eventType)) {
            $form->freeze('event_type');
            $form->addText('title', get_lang('Title'));
            $form->addTextarea('content', get_lang('Content'));
            $form->addText('link', get_lang('Link'), false);
            $form->addCheckBox('persistent', get_lang('Persistent'));
            $form->addNumeric('day_diff', get_lang('Time difference'), false);

            switch ($eventType) {
                case self::JUSTIFICATION_EXPIRATION:
                    $plugin = Justification::create();
                    $list = $plugin->getList();
                    $list = array_column($list, 'name', 'id');
                    $form->addSelect('event_id', get_lang('Justification type'), $list);
                    break;
                default:
                    break;
            }
            $form->addButtonSave(get_lang('Save'));
        }

        return $form;
    }

    /**
     * Get notification-related user's extra field value
     * @param int $userId
     * @return string
     */
    public function getUserExtraData(int $userId): string
    {
        $data = UserManager::get_extra_user_data_by_field($userId, $this->extraFieldName);

        return isset($data['notification_event']) ? $data['notification_event'] : '';
    }

    public function getNotificationsByUser($userId)
    {
        $userInfo = api_get_user_info($userId);
        $events = $this->get_all();
        $extraFieldData = $this->getUserExtraData(api_get_user_id());
        $allowJustification = Container::getPluginHelper()->isPluginEnabled('Justification');

        $userJustificationList = [];
        if ($allowJustification) {
            $plugin = Justification::create();
            $userJustificationList = $plugin->getUserJustificationList($userId);
        }

        $notifications = [];
        foreach ($events as $event) {
            $days = (int) $event['day_diff'];
            $checkIsRead = 0 == $event['persistent'] ? true : false;
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
                    if ($showNotification && false === $read) {
                        $notifications[] = [
                            'id' => $id,
                            'title' => $event['title'],
                            'content' => $event['content'],
                            'event_text' => get_lang('Expiration date').': '.api_get_local_time($userInfo['expiration_date']),
                            'link' => $event['link'],
                            'persistent' => $event['persistent'],
                        ];
                    }
                    break;
                case self::JUSTIFICATION_EXPIRATION:
                    if (!empty($userJustificationList)) {
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
                            $eventText .= $plugin->get_lang('Justification expiration').': '.$userJustification['date_validity'];

                            $url = $event['link'];
                            if (empty($url)) {
                                $url = api_get_path(WEB_CODE_PATH).'auth/justification.php#'.$fieldData['code'];
                            }

                            if ($showNotification && false === $read) {
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
                    }
                    break;
            }
        }

        return $notifications;
    }

    /**
     * Returns whether a notification has already been read by the user or not
     * @param int    $id
     * @param string $extraData
     * @return bool
     */
    public function isRead(int $id, string $extraData): bool
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

    /**
     * Mark a notification as read by the user
     * @param int $id
     * @return bool
     */
    public function markAsRead(int $id): bool
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
     * Returns whether to show some notification or not based on dates
     * @param string $date
     * @param int $dayDiff
     * @return bool
     * @throws Exception
     */
    public function showNotification(string $date, int $dayDiff): bool
    {
        $today = api_get_utc_datetime();
        $dayDiff = (string) $dayDiff;
        $expiration = api_get_utc_datetime($date, false, true);
        $interval = new DateInterval('P'.$dayDiff.'D');
        $diff = $expiration->sub($interval);

        if ($diff->format('Y-m-d H:i:s') < $today) {
            return true;
        }

        return false;
    }
}
