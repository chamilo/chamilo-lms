<?php

/* For licensing terms, see /license.txt */

/**
 * Class SendNotificationToPublishLp.
 */
class SendNotificationToPublishLp extends Plugin
{
    /**
     * @var array
     */
    protected $notifyStudentField;

    /**
     * SendNotificationToPublishLp constructor.
     */
    protected function __construct()
    {
        $this->tblExtraFieldOption = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        parent::__construct(
            '1.0',
            'Carlos Alvarado'
        );
        $field = new ExtraField('lp');
        $notifyStudentField = $field->get_handler_field_info_by_field_variable('notify_student_and_hrm_when_available');

        if (empty($notifyStudentField)) {
            $notifyStudentField = [
                'field_type' => ExtraField::FIELD_TYPE_RADIO,
                'variable' => 'notify_student_and_hrm_when_available',
                'display_text' => 'NotifyStudentAndHrmWhenAvailable',
                'default_value' => 0,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 0,
            ];
        }
        $this->notifyStudentField = $notifyStudentField;
    }

    /**
     * Create a new instance of SendNotificationToPublishLp.
     *
     * @return SendNotificationToPublishLp
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Perform the plugin installation.
     */
    public function install()
    {
        $this->saveNotificationField();
        $this->setNotifyExtrafieldData();
    }

    /**
     * Save the arrangement for notify_student_and_hrm_when_available, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function saveNotificationField()
    {
        $schedule = new ExtraField('lp');
        $data = $this->getDataNotificationField();
        $data['default_value'] = 0;
        $data['visible_to_self'] = 1;
        $data['visible_to_others'] = 1;
        $data['changeable'] = 1;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }
        $field = new ExtraField('lp');
        $notifyStudentField = $field->get_handler_field_info_by_field_variable('notify_student_and_hrm_when_available');
        $this->notifyStudentField = $notifyStudentField;
    }

    /**
     * Make a array clean of notify_student_and_hrm_when_available.
     *
     * @return array|bool
     */
    public function getDataNotificationField($install = true)
    {
        $data = $this->notifyStudentField;

        $data['field_type'] = ExtraField::FIELD_TYPE_RADIO;
        $data['field_order'] = isset($data['field_order']) ? $data['field_order'] : $data['field_order']; // at
        $data['variable'] = isset($data['variable']) ? $data['variable'] : 'notify_student_and_hrm_when_available';
        $data['display_text'] = isset($data['display_text']) ? $data['display_text'] : 'NotifyStudentAndHrmWhenAvailable';
        $data['default_value'] = (int) $install;
        $data['field_order'] = isset($data['field_order']) ? $data['field_order'] : 0;
        $data['visible_to_self'] = isset($data['visible_to_self']) ? $data['visible_to_self'] : 0;
        $data['visible_to_others'] = isset($data['visible_to_others']) ? $data['visible_to_others'] : 0;
        $data['changeable'] = isset($data['changeable']) ? $data['changeable'] : 0;
        $data['filter'] = isset($data['filter']) ? $data['filter'] : 0;

        return $data;
    }

    /**
     * Set default_value to 0.
     */
    public function uninstall()
    {
        $schedule = new ExtraField('lp');
        $data = $this->getDataNotificationField(false);
        $data['default_value'] = 0;
        $data['visible_to_self'] = 0;
        $data['visible_to_others'] = 0;
        $data['changeable'] = 0;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }
    }

    /**
     * Insert the option fields for notify with the generic values yes or not.
     */
    public function setNotifyExtrafieldData()
    {
        $options = [
            0 => get_lang('No'),
            1 => get_lang('Yes'),
        ];
        $notifyId = (int) $this->notifyStudentField['id'];
        if ($notifyId != 0) {
            for ($i = 0; $i < count($options); $i++) {
                $order = $i + 1;
                $extraFieldOptionValue = $options[$i];
                $query = "SELECT *
                          FROM ".$this->tblExtraFieldOption."
                          WHERE
                                option_value = $i AND
                                field_id = $notifyId";

                $extraFieldOption = Database::fetch_assoc(Database::query($query));
                $extraFieldId = isset($extraFieldOption['id']) ? (int) ($extraFieldOption['id']) : 0;

                if (
                    $extraFieldId != 0
                    && $extraFieldOption['field_id'] == $notifyId) {
                    // Update?
                    $query = "UPDATE ".$this->tblExtraFieldOption."
                        SET
                            option_value = $i,
                            option_order = $order,
                            display_text = '$extraFieldOptionValue'
                        WHERE
                            field_id = $notifyId
                            AND id = $extraFieldId";
                } else {
                    $query = "
                        INSERT INTO ".$this->tblExtraFieldOption."
                            (field_id, option_value, display_text, priority, priority_message, option_order) VALUES
                            ( '$notifyId', $i, '$extraFieldOptionValue', NULL, NULL, '$order');
                        ";
                }
                Database::query($query);
            }
        }
    }
}
