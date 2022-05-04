<?php
/* For licensing terms, see /license.txt */

/**
 * Class CatForm.
 *
 * @author Stijn Konings
 */
class CatForm extends FormValidator
{
    public const TYPE_ADD = 1;
    public const TYPE_EDIT = 2;
    public const TYPE_MOVE = 3;
    public const TYPE_SELECT_COURSE = 4;
    /** @var Category */
    private $category_object;

    /**
     * CatForm constructor.
     * Builds a form containing form items based on a given parameter.
     *
     * @param string $form_type       1=add, 2=edit,3=move,4=browse
     * @param string $category_object
     * @param string $form_name
     * @param string $method
     * @param null   $action
     */
    public function __construct(
        $form_type,
        $category_object,
        $form_name,
        $method = 'post',
        $action = null
    ) {
        parent::__construct($form_name, $method, $action);
        $this->form_type = $form_type;
        if (isset($category_object)) {
            $this->category_object = $category_object;
        }

        switch ($this->form_type) {
            case self::TYPE_EDIT:
                $this->build_editing_form();
                break;
            case self::TYPE_ADD:
                $this->build_add_form();
                break;
            case self::TYPE_MOVE:
                $this->build_move_form();
                break;
            case self::TYPE_SELECT_COURSE:
                $this->build_select_course_form();
                break;
        }

        $this->setDefaults();
    }

    /**
     * This function will build a move form that will allow the user to move a category to
     * a another.
     */
    protected function build_move_form()
    {
        $renderer = &$this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span> ');
        $this->addElement(
            'static',
            null,
            null,
            '"'.$this->category_object->get_name().'" '
        );
        $this->addElement('static', null, null, get_lang('MoveTo').' : ');
        $select = $this->addElement('select', 'move_cat', null, null);
        $line = null;
        foreach ($this->category_object->get_target_categories() as $cat) {
            for ($i = 0; $i < $cat[2]; $i++) {
                $line .= '--';
            }
            if ($cat[0] != $this->category_object->get_parent_id()) {
                $select->addOption($line.' '.$cat[1], $cat[0]);
            } else {
                $select->addOption($line.' '.$cat[1], $cat[0], 'disabled');
            }
            $line = '';
        }
        $this->addElement('submit', null, get_lang('Ok'));
    }

    /**
     * This function builds an 'add category form, if parent id is 0, it will only
     * show courses.
     */
    protected function build_add_form()
    {
        // check if we are a root category
        // if so, you can only choose between courses
        if ('0' == $this->category_object->get_parent_id()) {
            $this->setDefaults(
                [
                    'select_course' => $this->category_object->get_course_code(),
                    'hid_user_id' => $this->category_object->get_user_id(),
                    'hid_parent_id' => $this->category_object->get_parent_id(),
                ]
            );
        } else {
            $this->setDefaults(
                [
                    'hid_user_id' => $this->category_object->get_user_id(),
                    'hid_parent_id' => $this->category_object->get_parent_id(),
                ]
            );
            $this->addElement(
                'hidden',
                'course_code',
                $this->category_object->get_course_code()
            );
        }
        $this->build_basic_form();
    }

    /**
     * Builds an form to edit a category.
     */
    protected function build_editing_form()
    {
        $skills = $this->category_object->getSkillsForSelect();

        $course_code = api_get_course_id();
        $session_id = api_get_session_id();

        $test_cats = Category::load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            false
        );

        $links = null;
        if (isset($test_cats[0])) {
            $links = $test_cats[0]->get_links();
        }
        $grade_model_id = $this->category_object->get_grade_model_id();

        if (empty($links)) {
            $grade_model_id = 0;
        }

        $category_name = $this->category_object->get_name();

        // The main course category:
        if (isset($this->category_object) && 0 == $this->category_object->get_parent_id()) {
            if (empty($category_name)) {
                $category_name = $course_code;
            }
        }

        $this->setDefaults(
            [
                'name' => $category_name,
                'description' => $this->category_object->get_description(),
                'hid_user_id' => $this->category_object->get_user_id(),
                'hid_parent_id' => $this->category_object->get_parent_id(),
                'grade_model_id' => $grade_model_id,
                'skills' => $skills,
                'weight' => $this->category_object->get_weight(),
                'visible' => $this->category_object->is_visible(),
                'certif_min_score' => $this->category_object->getCertificateMinScore(),
                'generate_certificates' => $this->category_object->getGenerateCertificates(),
                'is_requirement' => $this->category_object->getIsRequirement(),
            ]
        );

        $this->addElement('hidden', 'hid_id', $this->category_object->get_id());
        $this->addElement(
            'hidden',
            'course_code',
            $this->category_object->get_course_code()
        );
        $this->build_basic_form();
    }

    /**
     * This function builds an 'select course' form in the add category process,
     * if parent id is 0, it will only show courses.
     */
    protected function build_select_course_form()
    {
        $select = $this->addElement(
            'select',
            'select_course',
            [get_lang('PickACourse'), 'test'],
            null
        );
        $courses = Category::get_all_courses(api_get_user_id());
        //only return courses that are not yet created by the teacher
        foreach ($courses as $row) {
            $select->addOption($row[1], $row[0]);
        }
        $this->setDefaults([
            'hid_user_id' => $this->category_object->get_user_id(),
            'hid_parent_id' => $this->category_object->get_parent_id(),
        ]);
        $this->addElement('hidden', 'hid_user_id');
        $this->addElement('hidden', 'hid_parent_id');
        $this->addElement('submit', null, get_lang('Ok'));
    }

    private function build_basic_form()
    {
        $this->addText(
            'name',
            get_lang('CategoryName'),
            true,
            ['maxlength' => '50']
        );
        $this->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        if (isset($this->category_object) &&
            $this->category_object->get_parent_id() == 0
        ) {
            // we can't change the root category
            $this->freeze('name');
        }

        $global_weight = api_get_setting('gradebook_default_weight');

        $value = 100;
        if (isset($global_weight)) {
            $value = $global_weight;
        }

        $this->addFloat(
            'weight',
            [
                get_lang('TotalWeight'),
                get_lang('TotalSumOfWeights'),
            ],
            true,
            ['value' => $value, 'maxlength' => '5']
        );

        $skillsDefaults = [];

        $allowSkillEdit = api_is_platform_admin() || api_is_drh();
        if (api_get_configuration_value('skills_teachers_can_assign_skills')) {
            $allowSkillEdit = $allowSkillEdit || api_is_allowed_to_edit();
        }

        if ($allowSkillEdit) {
            if (Skill::isToolAvailable()) {
                $skillSelect = $this->addElement(
                    'select_ajax',
                    'skills',
                    [
                        get_lang('Skills'),
                        get_lang('SkillsAchievedWhenAchievingThisGradebook'),
                    ],
                    null,
                    [
                        'id' => 'skills',
                        'multiple' => 'multiple',
                        'url' => api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=search_skills',
                    ]
                );

                // The magic should be here
                $skills = $this->category_object->get_skills();
                foreach ($skills as $skill) {
                    $skillsDefaults[] = $skill['id'];
                    $skillSelect->addOption($skill['name'], $skill['id']);
                }
            }
        }

        $defaultCertification = 0;
        if (!empty($this->category_object)) {
            $defaultCertification = $this->category_object->getCertificateMinScore();
        }

        if (isset($this->category_object) &&
            0 == $this->category_object->get_parent_id()
        ) {
            $model = ExerciseLib::getCourseScoreModel();
            if (empty($model)) {
                $this->addText(
                    'certif_min_score',
                    get_lang('CertificateMinScore'),
                    true,
                    ['maxlength' => '5']
                );

                if (true === api_get_configuration_value('gradebook_enable_subcategory_skills_independant_assignement')) {
                    // It allows the acquisition of competencies independently in the subcategories
                    $allowSkillsBySubCategory = $this->addCheckBox(
                        'allow_skills_by_subcategory',
                        [
                            null,
                            get_lang('ItAllowsTheAcquisitionOfSkillsBySubCategories'),
                        ],
                        get_lang('AllowsSkillsBySubCategories')
                    );
                    $allowSkillsBySubCategory->setChecked($this->category_object->getAllowSkillBySubCategory());
                }
            } else {
                $questionWeighting = $value;
                $defaultCertification = api_number_format($this->category_object->getCertificateMinScore(), 2);
                $select = $this->addSelect(
                    'certif_min_score',
                    get_lang('CertificateMinScore'),
                    [],
                    ['disable_js' => true]
                );

                foreach ($model['score_list'] as $item) {
                    $i = api_number_format($item['score_to_qualify'] / 100 * $questionWeighting, 2);
                    $model = ExerciseLib::getModelStyle($item, $i);
                    $attributes = ['class' => $item['css_class']];
                    if ($defaultCertification == $i) {
                        $attributes['selected'] = 'selected';
                    }
                    $select->addOption($model, $i, $attributes);
                }
                $select->updateSelectWithSelectedOption($this);
            }

            $this->addRule(
                'certif_min_score',
                get_lang('OnlyNumbers'),
                'numeric'
            );
            $this->addRule(
                'certif_min_score',
                get_lang('NegativeValue'),
                'compare',
                '>=',
                'server',
                false,
                false,
                0
            );
        } else {
            // It enables minimun score to get the skills independant assigment
            if (true === api_get_configuration_value('gradebook_enable_subcategory_skills_independant_assignement')) {
                $allowSkillsBySubCategory = $this->category_object->getAllowSkillBySubCategory($this->category_object->get_parent_id());
                if ($allowSkillsBySubCategory) {
                    $this->addText(
                        'certif_min_score',
                        get_lang('SkillMinScore'),
                        true,
                        ['maxlength' => '5']
                    );
                    $this->addRule(
                        'certif_min_score',
                        get_lang('OnlyNumbers'),
                        'numeric'
                    );
                    $this->addRule(
                        'certif_min_score',
                        get_lang('NegativeValue'),
                        'compare',
                        '>=',
                        'server',
                        false,
                        false,
                        0
                    );
                }
            }
            $this->addElement('checkbox', 'visible', null, get_lang('Visible'));
        }

        $this->addElement('hidden', 'hid_user_id');
        $this->addElement('hidden', 'hid_parent_id');
        $this->addElement(
            'textarea',
            'description',
            get_lang('Description')
        );

        if (isset($this->category_object) &&
            $this->category_object->get_parent_id() == 0 &&
            (api_is_platform_admin() || api_get_setting('teachers_can_change_grade_model_settings') == 'true')
        ) {
            // Getting grade models
            $obj = new GradeModel();
            $obj->fill_grade_model_select_in_form(
                $this,
                'grade_model_id',
                $this->category_object->get_grade_model_id()
            );

            // Freeze or not
            $course_code = api_get_course_id();
            $session_id = api_get_session_id();
            $test_cats = Category::load(
                null,
                null,
                $course_code,
                null,
                null,
                $session_id,
                false
            );
            $links = null;
            if (!empty($test_cats[0])) {
                $links = $test_cats[0]->get_links();
            }

            if (count($test_cats) > 1 || !empty($links)) {
                if ('true' == api_get_setting('gradebook_enable_grade_model')) {
                    $this->freeze('grade_model_id');
                }
            }

            $generateCertificatesParams = [];
            if ($this->category_object->getGenerateCertificates()) {
                $generateCertificatesParams['checked'] = 'checked';
            }

            $this->addElement(
                'checkbox',
                'generate_certificates',
                null,
                get_lang('GenerateCertificates'),
                $generateCertificatesParams
            );
        }

        //if (!empty($session_id)) {
        $isRequirementCheckbox = $this->addCheckBox(
                'is_requirement',
                [
                    null,
                    get_lang('ConsiderThisGradebookAsRequirementForASessionSequence'),
                ],
                get_lang('IsRequirement')
            );
        //}

        if ($this->category_object->getIsRequirement()) {
            $isRequirementCheckbox->setChecked(true);
        }

        $documentId = $this->category_object->getDocumentId();
        if (!empty($documentId)) {
            $documentData = DocumentManager::get_document_data_by_id($documentId, api_get_course_id());

            if (!empty($documentData)) {
                $this->addLabel(get_lang('Certificate'), $documentData['title']);
            }
        }

        if ($this->form_type == self::TYPE_ADD) {
            $this->addButtonCreate(get_lang('AddCategory'));
        } else {
            $this->addElement('hidden', 'editcat', intval($_GET['editcat']));
            $this->addButtonUpdate(get_lang('EditCategory'));
        }

        $setting = api_get_setting('tool_visible_by_default_at_creation');
        $visibility_default = 1;
        if (isset($setting['gradebook']) && $setting['gradebook'] == 'false') {
            $visibility_default = 0;
        }

        $this->setDefaults(
            [
                'visible' => $visibility_default,
                'skills' => $skillsDefaults,
                'certif_min_score' => (string) $defaultCertification,
            ]
        );
    }
}
