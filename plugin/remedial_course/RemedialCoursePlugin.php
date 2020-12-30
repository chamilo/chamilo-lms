<?php

/* For licensing terms, see /license.txt */

/**
 * Class RemedialCoursePlugin.
 */
class RemedialCoursePlugin extends Plugin
{
    /**
     * @var array
     */
    protected $remedialField;
    /**
     * @var array
     */
    protected $remedialAdvanceField;
    //advancedCourseList

    /**
     * RemedialCoursePlugin constructor.
     */
    public function __construct()
    {
        parent::__construct(
            '1.0',
            'Carlos Alvarado'
        );
        $field = new ExtraField('exercise');
        $remedialField = $field->get_handler_field_info_by_field_variable('remedialcourselist');

        if (empty($remedialField)) {
            $remedialField = [
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => 'remedialcourselist',
                'display_text' => 'remedialCourseList',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
            ];
        }
        $this->remedialField = $remedialField;
        //Advance
        $field = new ExtraField('exercise');
        $remedialAdvanceField = $field->get_handler_field_info_by_field_variable('advancedCourseList');

        if (empty($remedialAdvanceField)) {
            $remedialAdvanceField = [
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => 'advancedcourselist',
                'display_text' => 'advancedCourseList',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
            ];
        }
        $this->remedialAdvanceField = $remedialAdvanceField;
    }

    /**
     * Create a new instance of RemedialCoursePlugin.
     *
     * @return RemedialCoursePlugin
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
        $this->SaveAdvanceRemedialField();
    }

    /**
     * Save the arrangement for remedialcourselist, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function SaveRemedialField()
    {
        $schedule = new ExtraField('exercise');
        $data = $this->getDataRemedialField();
        $data['default_value'] = 1;
        $data['visible_to_self'] = 1;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }
    }

    /**
     * Save the arrangement for remedialadvancecourselist, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function SaveAdvanceRemedialField()
    {
        $schedule = new ExtraField('exercise');
        $data = $this->getDataAdvanceRemedialField();
        $data['default_value'] = 1;
        $data['visible_to_self'] = 1;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }
    }

    /**
     * Make a array clean of remedialcourselist.
     *
     * @return array|bool
     */
    public function getDataRemedialField($install = true)
    {
        $data = $this->remedialField;

        $data['field_type'] = isset($data['field_type']) ? $data['field_type'] : ExtraField::FIELD_TYPE_SELECT_MULTIPLE;
        $data['field_order'] = isset($data['field_order']) ? $data['field_order'] : $data['field_order']; // at
        $data['variable'] = isset($data['variable']) ? $data['variable'] : 'remedialcourselist';
        $data['display_text'] = isset($data['display_text']) ? $data['display_text'] : 'remedialCourseList';
        $data['default_value'] = (int) $install;
        $data['field_order'] = isset($data['field_order']) ? $data['field_order'] : 0;
        $data['visible_to_self'] = isset($data['visible_to_self']) ? $data['visible_to_self'] : 1;
        $data['visible_to_others'] = isset($data['visible_to_others']) ? $data['visible_to_others'] : 0;
        $data['changeable'] = isset($data['changeable']) ? $data['changeable'] : 1;
        $data['filter'] = isset($data['filter']) ? $data['filter'] : 0;

        return $data;
    }

    /**
     * Make a array clean of advancedcourselist.
     *
     * @return array|bool
     */
    public function getDataAdvanceRemedialField($install = true)
    {
        $data = $this->remedialAdvanceField;

        $data['field_type'] = isset($data['field_type']) ? $data['field_type'] : ExtraField::FIELD_TYPE_SELECT_MULTIPLE;
        $data['field_order'] = isset($data['field_order']) ? $data['field_order'] : $data['field_order']; // at
        $data['variable'] = isset($data['variable']) ? $data['variable'] : 'advancedcourselist';
        $data['display_text'] = isset($data['display_text']) ? $data['display_text'] : 'advancedCourseList';
        $data['default_value'] = (int) $install;
        $data['field_order'] = isset($data['field_order']) ? $data['field_order'] : 0;
        $data['visible_to_self'] = isset($data['visible_to_self']) ? $data['visible_to_self'] : 1;
        $data['visible_to_others'] = isset($data['visible_to_others']) ? $data['visible_to_others'] : 0;
        $data['changeable'] = isset($data['changeable']) ? $data['changeable'] : 1;
        $data['filter'] = isset($data['filter']) ? $data['filter'] : 0;

        return $data;
    }

    /**
     * Set default_value to 0.
     */
    public function uninstall()
    {
        $schedule = new ExtraField('exercise');
        $data = $this->getDataRemedialField(false);
        $data['default_value'] = 0;
        $data['visible_to_self'] = 0;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }

        //advance
        $schedule = new ExtraField('exercise');
        $data = $this->getDataAdvanceRemedialField(false);
        $data['default_value'] = 0;
        $data['visible_to_self'] = 0;
        if (isset($data['id'])) {
            $schedule->update($data);
        } else {
            $schedule->save($data);
        }
    }
}
