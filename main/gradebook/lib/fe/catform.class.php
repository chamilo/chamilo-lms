<?php
/* For licensing terms, see /license.txt */

/**
 * Class CatForm
 *
 * @author Stijn Konings
 * @package chamilo.gradebook
 */
class CatForm extends FormValidator
{
    const TYPE_ADD = 1;
    const TYPE_EDIT = 2;
    const TYPE_MOVE = 3;
    const TYPE_SELECT_COURSE = 4;
    private $category_object;

    /**
     * Builds a form containing form items based on a given parameter
     * @param int form_type 1=add, 2=edit,3=move,4=browse
     * @param obj cat_obj the category object
     * @param string form name
     * @param method method
     */
    public function __construct(
        $form_type,
        $category_object,
        $form_name,
        $method = 'post',
        $action = null
    ) {
        parent :: __construct($form_name, $method, $action);
        $this->form_type = $form_type;
        if (isset ($category_object)) {
            $this->category_object = $category_object;
        }
        if ($this->form_type == self :: TYPE_EDIT) {
            $this->build_editing_form();
        } elseif ($this->form_type == self :: TYPE_ADD) {
            $this->build_add_form();
        } elseif ($this->form_type == self :: TYPE_MOVE) {
            $this->build_move_form();
        } elseif ($this->form_type == self :: TYPE_SELECT_COURSE) {
            $this->build_select_course_form();
        }
        $this->setDefaults();
    }

    /**
     * This function will build a move form that will allow the user to move a category to
     * a another
     */
    protected function build_move_form()
    {
        $renderer =& $this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span> ');
        $this->addElement(
            'static',
            null,
            null,
            '"' . $this->category_object->get_name() . '" '
        );
        $this->addElement('static', null, null, get_lang('MoveTo') . ' : ');
        $select = $this->addElement('select', 'move_cat', null, null);
        $line = null;
        foreach ($this->category_object->get_target_categories() as $cat) {
            for ($i = 0; $i < $cat[2]; $i++) {
                $line .= '--';
            }
            if ($cat[0] != $this->category_object->get_parent_id()) {
                $select->addoption($line . ' ' . $cat[1], $cat[0]);
            } else {
                $select->addoption($line . ' ' . $cat[1], $cat[0], 'disabled');
            }
            $line = '';
        }
        $this->addElement('submit', null, get_lang('Ok'));
    }

    /**
     * This function builds an 'add category form, if parent id is 0, it will only
     * show courses
     */
    protected function build_add_form()
    {
        //check if we are a root category
        //if so, you can only choose between courses
        if ($this->category_object->get_parent_id() == '0') {
            //$select = $this->addElement('select','select_course',array(get_lang('PickACourse'),'test'), null);
            $coursecat = Category :: get_not_created_course_categories(
                api_get_user_id()
            );
            if (count($coursecat) == 0) {
                //$select->addoption(get_lang('CourseIndependent'),'COURSEINDEPENDENT','disabled');
            } else {
                //$select->addoption(get_lang('CourseIndependent'),'COURSEINDEPENDENT');
            }
            //only return courses that are not yet created by the teacher
            if (!empty($coursecat)) {
                foreach ($coursecat as $row) {
                    //$select->addoption($row[1],$row[0]);
                }
            } else {
                //$select->addoption($row[1],$row[0]);
            }
            $this->setDefaults(
                array(
                    'select_course' => $this->category_object->get_course_code(
                    ),
                    'hid_user_id' => $this->category_object->get_user_id(),
                    'hid_parent_id' => $this->category_object->get_parent_id()
                )
            );
        } else {
            $this->setDefaults(
                array(
                    'hid_user_id' => $this->category_object->get_user_id(),
                    'hid_parent_id' => $this->category_object->get_parent_id()
                )
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
     * Builds an form to edit a category
     */
    protected function build_editing_form()
    {
        $skills = $this->category_object->get_skills_for_select();

        $course_code = api_get_course_id();
        $session_id = api_get_session_id();
        //Freeze or not
        $test_cats = Category::load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            false
        ); //already init
        $links = null;
        if (isset($test_cats[0])) {
            $links = $test_cats[0]->get_links();
        }
        $grade_model_id = $this->category_object->get_grade_model_id();

        if (empty($links)) {
            $grade_model_id    = 0;
        }

        $category_name = $this->category_object->get_name();

        // The main course category:
        if (isset($this->category_object) && $this->category_object->get_parent_id() == 0) {
            if (empty($category_name)) {
                $category_name = $course_code;
            }
        }

        $this->setDefaults(
            array(
                'name' 				=> $category_name,
                'description' 		=> $this->category_object->get_description(),
                'hid_user_id' 		=> $this->category_object->get_user_id(),
                'hid_parent_id' 	=> $this->category_object->get_parent_id(),
                'grade_model_id' 	=> $grade_model_id,
                'skills'            => $skills,
                'weight' 			=> $this->category_object->get_weight(),
                'visible' 			=> $this->category_object->is_visible(),
                'certif_min_score'  => $this->category_object->get_certificate_min_score(),
                'generate_certificates' => $this->category_object->getGenerateCertificates(),
                'is_requirement' => $this->category_object->getIsRequirement()
            )
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
     *
     */
    private function build_basic_form()
    {
        $this->addElement('hidden', 'zero', 0);
        $this->addText(
            'name',
            get_lang('CategoryName'),
            true,
            array('maxlength' => '50')
        );
        $this->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        if (isset($this->category_object) &&
            $this->category_object->get_parent_id() == 0
        ) {
            //we can't change the root category
            $this->freeze('name');
        }

        $global_weight = api_get_setting('gradebook_default_weight');

        if (isset($global_weight)) {
            $value = $global_weight;
        } else {
            $value = 100;
        }
        $this->addText('weight',
            array(
                get_lang('TotalWeight'),
                get_lang('TotalSumOfWeights')
            ),
            true,
            array('value' => $value, 'class' => 'span1', 'maxlength' => '5')
        );
        $this->addRule('weight', get_lang('ThisFieldIsRequired'), 'required');

        $skillsDefaults = [];

        if (api_is_platform_admin() || api_is_drh()) {
            if (api_get_setting('allow_skills_tool') == 'true') {
                $skillSelect = $this->addElement(
                    'select_ajax',
                    'skills',
                    array(
                        get_lang('Skills'),
                        get_lang('SkillsAchievedWhenAchievingThisGradebook')
                    ),
                    null,
                    [
                        'id' => 'skills',
                        'multiple' => 'multiple',
                        'url' => api_get_path(WEB_AJAX_PATH) . 'skill.ajax.php?a=search_skills'
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

        if (isset($this->category_object) &&
            $this->category_object->get_parent_id() == 0
        ) {
            $this->addText(
                'certif_min_score',
                get_lang('CertificateMinScore'),
                false,
                array('class' => 'span1', 'maxlength' => '5')
            );
            $this->addRule(
                'certif_min_score',
                get_lang('ThisFieldIsRequired'),
                'required'
            );
            $this->addRule(
                'certif_min_score',
                get_lang('OnlyNumbers'),
                'numeric'
            );
            $this->addRule(
                array('certif_min_score', 'zero'),
                get_lang('NegativeValue'),
                'compare',
                '>='
            );
        } else {
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
            (api_is_platform_admin() || api_get_setting(
                    'teachers_can_change_grade_model_settings'
                ) == 'true')
        ) {

            //Getting grade models
            $obj = new GradeModel();
            $obj->fill_grade_model_select_in_form(
                $this,
                'grade_model_id',
                $this->category_object->get_grade_model_id()
            );

            // Freeze or not
            $course_code = api_get_course_id();
            $session_id = api_get_session_id();
            $test_cats = Category :: load(
                null,
                null,
                $course_code,
                null,
                null,
                $session_id,
                false
            ); //already init
            $links = null;
            if (!empty($test_cats[0])) {
                $links = $test_cats[0]->get_links();
            }

            if (count($test_cats) > 1 || !empty($links)) {
                if (api_get_setting('gradebook_enable_grade_model') == 'true') {
                    $this->freeze('grade_model_id');
                }
            }

            $generateCertificatesParams = array();
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

        if (!empty($session_id)) {
            $isRequirementCheckbox = $this->addCheckBox(
                'is_requirement',
                [
                    null,
                    get_lang('ConsiderThisGradebookAsRequirementForASessionSequence')
                ],
                get_lang('IsRequirement')
            );
        }

        if ($this->category_object->getIsRequirement()) {
            $isRequirementCheckbox->setChecked(true);
        }

        if ($this->form_type == self :: TYPE_ADD) {
            $this->addButtonCreate(get_lang('AddCategory'));
        } else {
            $this->addElement('hidden', 'editcat', intval($_GET['editcat']));
            $this->addButtonUpdate(get_lang('EditCategory'));
        }

        $this->addRule('weight', get_lang('OnlyNumbers'), 'numeric');
        $this->addRule(
            array('weight', 'zero'),
            get_lang('NegativeValue'),
            'compare',
            '>='
        );

        $setting = api_get_setting('tool_visible_by_default_at_creation');
        $visibility_default = 1;
        if (isset($setting['gradebook']) && $setting['gradebook'] == 'false') {
            $visibility_default = 0;
        }
        $this->setDefaults(array('visible' => $visibility_default, 'skills' => $skillsDefaults));
    }

    /**
     * This function builds an 'select course' form in the add category process,
     * if parent id is 0, it will only show courses
     */
    protected function build_select_course_form()
    {
        $select = $this->addElement(
            'select',
            'select_course',
            array(get_lang('PickACourse'), 'test'),
            null
        );
        $coursecat = Category :: get_all_courses(api_get_user_id());
        //only return courses that are not yet created by the teacher

        foreach ($coursecat as $row) {
            $select->addoption($row[1],$row[0]);
        }
        $this->setDefaults(array(
            'hid_user_id' => $this->category_object->get_user_id(),
            'hid_parent_id' => $this->category_object->get_parent_id()
        ));
        $this->addElement('hidden','hid_user_id');
        $this->addElement('hidden','hid_parent_id');
        $this->addElement('submit', null, get_lang('Ok'));
    }

    function display()
    {
        parent :: display();
    }

    function setDefaults($defaults = array(), $filter = null)
    {
        parent::setDefaults($defaults, $filter);
    }
}
