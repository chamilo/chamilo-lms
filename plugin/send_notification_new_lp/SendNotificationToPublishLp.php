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
    public function __construct()
    {
        parent::__construct(
            '1.0',
            'Carlos Alvarado'
        );
        $field = new ExtraField('lp');
        $notifyStudentField = $field->get_handler_field_info_by_field_variable('notify_student_and_hrm_when_available');

        if (empty($notifyStudentField)) {
            $notifyStudentField = [
                'field_type' => ExtraField::FIELD_TYPE_INTEGER,
                'variable' => 'notify_student_and_hrm_when_available',
                'display_text' => 'NotifyStudentAndHrmWhenAvailable',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 0,
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
        $this->SaveRemedialField();
    }

    /**
     * Save the arrangement for notify_student_and_hrm_when_available, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function SaveRemedialField()
    {
        $schedule = new ExtraField('lp');
        $data = $this->getDataRemedialField();
        $data['default_value'] = 1;
        $data['visible_to_self'] = 0;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }
    }

    /**
     * Make a array clean of notify_student_and_hrm_when_available.
     *
     * @return array|bool
     */
    public function getDataRemedialField($install = true)
    {
        $data = $this->notifyStudentField;

        $data['field_type'] = isset($data['field_type']) ? $data['field_type'] : ExtraField::FIELD_TYPE_INTEGER;
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
        $data = $this->getDataRemedialField(false);
        $data['default_value'] = 0;
        $data['visible_to_self'] = 0;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }

    }
}
