<?php
/* For licensing terms, see /license.txt */

class OutcomeForm extends EvalForm
{
    /**
     * Builds a form containing form items based on a given parameter.
     *
     * @param int        $form_type         1=add, 2=edit,3=move,4=result_add
     * @param Evaluation $evaluation_object the category object
     * @param obj        $result_object     the result object
     * @param string     $form_name
     * @param string     $method
     * @param string     $action
     */
    public function __construct(
        $evaluation_object,
        $result_object,
        $form_name,
        $method = 'post',
        $action = null,
        $extra1 = null,
        $extra2 = null
    ) {
        parent::__construct(
            -1,
            $evaluation_object,
            $result_object,
            $form_name,
            $method,
            $action,
            $extra1,
            $extra2
        );

        $this->build_add_form();
        $this->setDefaults();
    }

    protected function build_add_form()
    {
        $this->setDefaults(
            [
                'hid_user_id' => $this->evaluation_object->get_user_id(),
                'hid_category_id' => $this->evaluation_object->get_category_id(),
                'hid_course_code' => $this->evaluation_object->get_course_code(),
                'created_at' => api_get_utc_datetime(),
            ]
        );
        $this->build_basic_form();

        $this->addButtonCreate(get_lang('AddAssessment'), 'submit');
    }

    /**
     * Builds a basic form that is used in add and edit.
     *
     * @param int $edit
     *
     * @throws Exception
     */
    private function build_basic_form($edit = 0)
    {
        $this->addElement('header', get_plugin_lang('NewOutcomeFormTitle'));
        $this->addElement('hidden', 'hid_user_id');
        $this->addElement('hidden', 'hid_course_code');

        $this->addText(
            'name',
            get_lang('EvaluationName'),
            true,
            [
                'maxlength' => '50',
                'id' => 'evaluation_title',
            ]
        );

        $cat_id = $this->evaluation_object->get_category_id();

        $session_id = api_get_session_id();
        $course_code = api_get_course_id();
        $all_categories = Category:: load(null, null, $course_code, null, null, $session_id, false);

        if (count($all_categories) == 1) {
            $this->addElement('hidden', 'hid_category_id', $cat_id);
        } else {
            $select_gradebook = $this->addElement(
                'select',
                'hid_category_id',
                get_lang('SelectGradebook'),
                [],
                ['id' => 'hid_category_id']
            );
            $this->addRule('hid_category_id', get_lang('ThisFieldIsRequired'), 'nonzero');
            $default_weight = 0;
            if (!empty($all_categories)) {
                foreach ($all_categories as $my_cat) {
                    if ($my_cat->get_course_code() == api_get_course_id()) {
                        $grade_model_id = $my_cat->get_grade_model_id();
                        if (empty($grade_model_id)) {
                            if ($my_cat->get_parent_id() == 0) {
                                $default_weight = $my_cat->get_weight();
                                $select_gradebook->addOption(get_lang('Default'), $my_cat->get_id());
                                $cats_added[] = $my_cat->get_id();
                            } else {
                                $select_gradebook->addOption($my_cat->get_name(), $my_cat->get_id());
                                $cats_added[] = $my_cat->get_id();
                            }
                        } else {
                            $select_gradebook->addOption(get_lang('Select'), 0);
                        }
                        if ($this->evaluation_object->get_category_id() == $my_cat->get_id()) {
                            $default_weight = $my_cat->get_weight();
                        }
                    }
                }
            }
        }

        $this->addFloat(
            'weight_mask',
            [
                get_lang('Weight'),
                null,
                ' [0 .. <span id="max_weight">'.$all_categories[0]->get_weight().'</span>] ',
            ],
            true,
            [
                'size' => '4',
                'maxlength' => '5',
            ]
        );

        if ($edit) {
            if (!$this->evaluation_object->has_results()) {
                $this->addText(
                    'max',
                    get_lang('QualificationNumeric'),
                    true,
                    [
                        'maxlength' => '5',
                    ]
                );
            } else {
                $this->addText(
                    'max',
                    [get_lang('QualificationNumeric'), get_lang('CannotChangeTheMaxNote')],
                    false,
                    [
                        'maxlength' => '5',
                        'disabled' => 'disabled',
                    ]
                );
            }
        } else {
            $this->addText(
                'max',
                get_lang('QualificationNumeric'),
                true,
                [
                    'maxlength' => '5',
                ]
            );
            $default_max = api_get_setting('gradebook_default_weight');
            $defaults['max'] = isset($default_max) ? $default_max : 100;
            $this->setDefaults($defaults);
        }

        $this->addElement('textarea', 'description', get_lang('Description'));
        $this->addRule('hid_category_id', get_lang('ThisFieldIsRequired'), 'required');
        $this->addElement('checkbox', 'visible', null, get_lang('Visible'));
        $this->addRule('max', get_lang('OnlyNumbers'), 'numeric');
        $this->addRule(
            'max',
            get_lang('NegativeValue'),
            'compare',
            '>=',
            'server',
            false,
            false,
            0
        );
        $setting = api_get_setting('tool_visible_by_default_at_creation');
        $visibility_default = 1;
        if (isset($setting['gradebook']) && $setting['gradebook'] == 'false') {
            $visibility_default = 0;
        }
        $this->setDefaults(['visible' => $visibility_default]);
    }
}
