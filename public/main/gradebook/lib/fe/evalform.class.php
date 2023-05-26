<?php
/* For licensing terms, see /license.txt */

/**
 * Class EvalForm.
 *
 * Extends FormValidator with add&edit forms for evaluations
 *
 * @author Stijn Konings
 */
class EvalForm extends FormValidator
{
    const TYPE_ADD = 1;
    const TYPE_EDIT = 2;
    const TYPE_MOVE = 3;
    const TYPE_RESULT_ADD = 4;
    const TYPE_RESULT_EDIT = 5;
    const TYPE_ALL_RESULTS_EDIT = 6;
    const TYPE_ADD_USERS_TO_EVAL = 7;

    protected $evaluation_object;
    private $result_object;
    private $extra;

    /**
     * Builds a form containing form items based on a given parameter.
     *
     * @param int        $form_type         1=add, 2=edit,3=move,4=result_add
     * @param Evaluation $evaluation_object the category object
     * @param            $result_object     the result object
     * @param string     $form_name
     * @param string     $method
     * @param string     $action
     */
    public function __construct(
        $form_type,
        $evaluation_object,
        $result_object,
        $form_name,
        $method = 'post',
        $action = null,
        $extra1 = null,
        $extra2 = null
    ) {
        parent::__construct($form_name, $method, $action);

        if (isset($evaluation_object)) {
            $this->evaluation_object = $evaluation_object;
        }
        if (isset($result_object)) {
            $this->result_object = $result_object;
        }
        if (isset($extra1)) {
            $this->extra = $extra1;
        }

        switch ($form_type) {
            case self::TYPE_EDIT:
                $this->build_editing_form();
                break;
            case self::TYPE_ADD:
                $this->build_add_form();
                break;
            case self::TYPE_MOVE:
                $this->build_editing_form();
                break;
            case self::TYPE_RESULT_ADD:
                $this->build_result_add_form();
                break;
            case self::TYPE_RESULT_EDIT:
                $this->build_result_edit_form();
                break;
            case self::TYPE_ALL_RESULTS_EDIT:
                $this->build_all_results_edit_form();
                break;
            case self::TYPE_ADD_USERS_TO_EVAL:
                $this->build_add_user_to_eval();
                break;
        }
        $this->setDefaults();
    }

    public function display()
    {
        parent::display();
    }

    public function setDefaults($defaultValues = [], $filter = null)
    {
        parent::setDefaults($defaultValues, $filter);
    }

    public function sort_by_user($item1, $item2)
    {
        $user1 = $item1['user'];
        $user2 = $item2['user'];
        if (api_sort_by_first_name()) {
            $result = api_strcmp($user1['firstname'], $user2['firstname']);
            if (0 == $result) {
                return api_strcmp($user1['lastname'], $user2['lastname']);
            }
        } else {
            $result = api_strcmp($user1['lastname'], $user2['lastname']);
            if (0 == $result) {
                return api_strcmp($user1['firstname'], $user2['firstname']);
            }
        }

        return $result;
    }

    /**
     * This form will build a form to add users to an evaluation.
     */
    protected function build_add_user_to_eval()
    {
        $this->addHeader(get_lang('Choose users for this evaluation'));
        $select = $this->addSelect(
            'firstLetterUser',
            get_lang('First letter'),
            null,
            [
                'onchange' => 'document.add_users_to_evaluation.submit()',
            ]
        );
        $select->addOption('', '');
        for ($i = 65; $i <= 90; $i++) {
            $letter = chr($i);
            if (isset($this->extra) && $this->extra == $letter) {
                $select->addOption($letter, $letter, 'selected');
            } else {
                $select->addOption($letter, $letter);
            }
        }
        $select = $this->addSelect(
            'add_users',
            null,
            null,
            [
                'multiple' => 'multiple',
                'size' => '15',
                'style' => 'width:250px',
            ]
        );
        foreach ($this->evaluation_object->get_not_subscribed_students() as $user) {
            if ((!isset($this->extra)) || empty($this->extra) || api_strtoupper(api_substr($user[1], 0, 1)) == $this->extra
            ) {
                $select->addOption($user[1].' '.$user[2].' ('.$user[3].')', $user[0]);
            }
        }
        $this->addButtonCreate(get_lang('Add users to evaluation'), 'submit_button');
    }

    /**
     * This function builds a form to edit all results in an evaluation.
     */
    protected function build_all_results_edit_form()
    {
        //extra field for check on maxvalue
        $this->addElement('header', get_lang('Grade learners'));
        $renderer = &$this->defaultRenderer();
        // set new form template
        $form_template = '<form{attributes}>
                <div class="table-responsive">
                    <table class="data_table" border="0" cellpadding="5" cellspacing="5">{content}</table>
                </div>
                </form>';
        $renderer->setFormTemplate($form_template);

        if (api_is_western_name_order()) {
            $renderer->setHeaderTemplate(
                '<tr>
    		      <th>'.get_lang('Code').'</th>
    		      <th>'.get_lang('Username').'</th>
    		      <th>'.get_lang('First name').'</th>
    		      <th>'.get_lang('Last name').'</th>
    		      <th>'.get_lang('Grade activity').'</th>
    		   </tr>'
            );
        } else {
            $renderer->setHeaderTemplate(
                '<tr>
                  <th>'.get_lang('Code').'</th>
                  <th>'.get_lang('Username').'</th>
                  <th>'.get_lang('Last name').'</th>
                  <th>'.get_lang('First name').'</th>
                  <th>'.get_lang('Grade activity').'</th>
               </tr>'
            );
        }
        $template_submit = '<tr>
            <td colspan="4" ></td>
            <td>
            {element}
            <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
            </td>
            </tr>';

        $results_and_users = [];
        foreach ($this->result_object as $result) {
            $user = api_get_user_info($result->get_user_id());
            $results_and_users[] = ['result' => $result, 'user' => $user];
        }
        usort($results_and_users, ['EvalForm', 'sort_by_user']);
        $defaults = [];

        $model = ExerciseLib::getCourseScoreModel();

        foreach ($results_and_users as $result_and_user) {
            $user = $result_and_user['user'];
            $result = $result_and_user['result'];
            $renderer = &$this->defaultRenderer();

            if (api_is_western_name_order()) {
                $user_info = '<td align="left" >'.$user['firstname'].'</td>';
                $user_info .= '<td align="left" >'.$user['lastname'].'</td>';
            } else {
                $user_info = '<td align="left" >'.$user['lastname'].'</td>';
                $user_info .= '<td align="left" >'.$user['firstname'].'</td>';
            }

            $template = '<tr>
		      <td align="left" >'.$user['official_code'].'</td>
		      <td align="left" >'.$user['username'].'</td>
		      '.$user_info.'
		       <td align="left">{element} / '.$this->evaluation_object->get_max().'
		         <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
		      </td>
		   </tr>';

            if (empty($model)) {
                $this->addFloat(
                    'score['.$result->get_id().']',
                    $this->build_stud_label($user['user_id'], $user['username'], $user['lastname'], $user['firstname']),
                    false,
                    [
                        'maxlength' => 5,
                    ],
                    false,
                    0,
                    $this->evaluation_object->get_max()
                );
                $defaults['score['.$result->get_id().']'] = $result->get_score();
            } else {
                $questionWeighting = $this->evaluation_object->get_max();
                $select = $this->addSelect(
                    'score['.$result->get_id().']',
                    get_lang('Score'),
                    [],
                    ['disable_js' => true, 'id' => 'score_'.$result->get_id()]
                );

                foreach ($model['score_list'] as $item) {
                    $i = api_number_format($item['score_to_qualify'] / 100 * $questionWeighting, 2);
                    $modelStyle = ExerciseLib::getModelStyle($item, $i);
                    $attributes = ['class' => $item['css_class']];
                    if ($result->get_score() == $i) {
                        $attributes['selected'] = 'selected';
                    }
                    $select->addOption($modelStyle, $i, $attributes);
                }
                $select->updateSelectWithSelectedOption($this);

                $template = '<tr>
                  <td align="left" >'.$user['official_code'].'</td>
                  <td align="left" >'.$user['username'].'</td>
                  '.$user_info.'
                   <td align="left">{element} <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
                  </td>
               </tr>';
            }
            $renderer->setElementTemplate($template, 'score['.$result->get_id().']');
        }

        if (empty($model)) {
            $this->setDefaults($defaults);
        }
        $this->addButtonSave(get_lang('Grade learners'));
        $renderer->setElementTemplate($template_submit, 'submit');
    }

    /**
     * This function builds a form to move an item to another category.
     */
    protected function build_move_form()
    {
        $renderer = &$this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span> ');
        $this->addElement('static', null, null, '"'.$this->evaluation_object->get_name().'" ');
        $this->addElement('static', null, null, get_lang('Move to').' : ');
        $select = $this->addSelect('move_cat', null, null);
        $line = '';
        foreach ($this->evaluation_object->get_target_categories() as $cat) {
            for ($i = 0; $i < $cat[2]; $i++) {
                $line .= '&mdash;';
            }
            $select->addOption($line.' '.$cat[1], $cat[0]);
            $line = '';
        }
        $this->addButtonSave(get_lang('Validate'), 'submit');
    }

    /**
     * Builds a result form containing inputs for all students with a given course_code.
     */
    protected function build_result_add_form()
    {
        $renderer = &$this->defaultRenderer();
        $renderer->setFormTemplate(
            '<form{attributes}>
            <div class="table-responsive">
                <table class="table table-hover table-striped data_table">
              {content}
		      </table>
            </div>
		   </form>'
        );

        $users = GradebookUtils::get_users_in_course($this->evaluation_object->get_course_code());
        $nr_users = 0;
        //extra field for check on maxvalue
        $this->addElement('hidden', 'maxvalue', $this->evaluation_object->get_max());
        $this->addElement('hidden', 'minvalue', 0);
        $this->addElement('header', get_lang('Grade learners'));

        if (api_is_western_name_order()) {
            $renderer->setHeaderTemplate(
                '<tr>
                  <th>'.get_lang('Code').'</th>
                  <th>'.get_lang('Username').'</th>
                  <th>'.get_lang('First name').'</th>
                  <th>'.get_lang('Last name').'</th>
                  <th>'.get_lang('Grade activity').'</th>
               </tr>'
            );
        } else {
            $renderer->setHeaderTemplate(
                '<tr>
                  <th>'.get_lang('Code').'</th>
                  <th>'.get_lang('Username').'</th>
                  <th>'.get_lang('Last name').'</th>
                  <th>'.get_lang('First name').'</th>
                  <th>'.get_lang('Grade activity').'</th>
               </tr>'
            );
        }

        $firstUser = true;
        foreach ($users as $user) {
            $element_name = 'score['.$user[0].']';
            $scoreColumnProperties = ['maxlength' => 5];
            if ($firstUser) {
                $scoreColumnProperties['autofocus'] = '';
                $firstUser = false;
            }

            //user_id, user.username, lastname, firstname
            $this->addFloat(
                $element_name,
                $this->build_stud_label($user[0], $user[1], $user[2], $user[3]),
                false,
                $scoreColumnProperties,
                false,
                0,
                $this->evaluation_object->get_max()
            );

            if (api_is_western_name_order()) {
                $user_info = '<td align="left" >'.$user[3].'</td>';
                $user_info .= '<td align="left" >'.$user[2].'</td>';
            } else {
                $user_info = '<td align="left" >'.$user[2].'</td>';
                $user_info .= '<td align="left" >'.$user[3].'</td>';
            }
            $nr_users++;

            $template = '<tr>
		      <td align="left" >'.$user[4].'</td>
		      <td align="left" >'.$user[1].'</td>
		      '.$user_info.'
		       <td align="left">{element} / '.$this->evaluation_object->get_max().'
		         <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
		      </td>
            </tr>';
            $renderer->setElementTemplate($template, $element_name);
        }
        $this->addElement('hidden', 'nr_users', $nr_users);
        $this->addElement('hidden', 'evaluation_id', $this->result_object->get_evaluation_id());
        $this->addButtonSave(get_lang('Grade learners'), 'submit');

        $template_submit = '<tr>
                <td colspan="4" ></td>
                <td >
                {element}
                    <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
                </td>
            </tr>';
        $renderer->setElementTemplate($template_submit, 'submit');
    }

    /**
     * Builds a form to edit a result.
     */
    protected function build_result_edit_form()
    {
        $userInfo = api_get_user_info($this->result_object->get_user_id());
        $this->addHeader(get_lang('User').': '.$userInfo['complete_name']);

        $model = ExerciseLib::getCourseScoreModel();

        if (empty($model)) {
            $this->addFloat(
                'score',
                [
                    get_lang('Score'),
                    null,
                    '/ '.$this->evaluation_object->get_max(),
                ],
                false,
                [
                    'size' => '4',
                    'maxlength' => '5',
                ],
                false,
                0,
                $this->evaluation_object->get_max()
            );
            $this->setDefaults(
                [
                    'score' => $this->result_object->get_score(),
                    'maximum' => $this->evaluation_object->get_max(),
                ]
            );
        } else {
            $questionWeighting = $this->evaluation_object->get_max();
            $select = $this->addSelect('score', get_lang('Score'), [], ['disable_js' => true]);

            foreach ($model['score_list'] as $item) {
                $i = api_number_format($item['score_to_qualify'] / 100 * $questionWeighting, 2);
                $model = ExerciseLib::getModelStyle($item, $i);
                $attributes = ['class' => $item['css_class']];
                if ($this->result_object->get_score() == $i) {
                    $attributes['selected'] = 'selected';
                }
                $select->addOption($model, $i, $attributes);
            }
            $select->updateSelectWithSelectedOption($this);
        }

        $allowMultipleAttempts = ('true' === api_get_setting('gradebook.gradebook_multiple_evaluation_attempts'));
        if ($allowMultipleAttempts) {
            $this->addTextarea('comment', get_lang('Comment'));
        }

        $this->addButtonSave(get_lang('Edit'));
        $this->addElement('hidden', 'hid_user_id', $this->result_object->get_user_id());
    }

    /**
     * Builds a form to add an evaluation.
     */
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
        if (null == $this->evaluation_object->get_course_code()) {
            $this->addElement('checkbox', 'adduser', null, get_lang('Add users to evaluation'));
        } else {
            $this->addElement('checkbox', 'addresult', null, get_lang('Grade learners'));
        }
        $this->addButtonCreate(get_lang('Add this classroom activity to the assessment'), 'submit');
    }

    /**
     * Builds a form to edit an evaluation.
     */
    protected function build_editing_form()
    {
        $parent_cat = Category::load($this->evaluation_object->get_category_id());
        //@TODO $weight_mask is replaced?
        if (0 == $parent_cat[0]->get_parent_id()) {
            $weight_mask = $this->evaluation_object->get_weight();
        } else {
            $cat = Category::load($parent_cat[0]->get_parent_id());
            $global_weight = $cat[0]->get_weight();
            $weight_mask = $global_weight * $this->evaluation_object->get_weight() / $parent_cat[0]->get_weight();
        }
        $weight = $weight_mask = $this->evaluation_object->get_weight();

        $this->setDefaults([
            'hid_id' => $this->evaluation_object->get_id(),
            'name' => $this->evaluation_object->get_name(),
            'description' => $this->evaluation_object->get_description(),
            'hid_user_id' => $this->evaluation_object->get_user_id(),
            'hid_course_code' => $this->evaluation_object->get_course_code(),
            'hid_category_id' => $this->evaluation_object->get_category_id(),
            'created_at' => api_get_utc_datetime($this->evaluation_object->get_date()),
            'weight' => $weight,
            'weight_mask' => $weight_mask,
            'max' => $this->evaluation_object->get_max(),
            'visible' => $this->evaluation_object->is_visible(),
        ]);
        $id_current = isset($this->id) ? $this->id : null;
        $this->addElement('hidden', 'hid_id', $id_current);
        $this->build_basic_form(1);
        $this->addButtonSave(get_lang('Save assessment'), 'submit');
    }

    /**
     * Builds a basic form that is used in add and edit.
     *
     * @param int $edit
     */
    private function build_basic_form($edit = 0)
    {
        $form_title = get_lang('Add classroom activity');
        if (!empty($_GET['editeval']) && 1 == $_GET['editeval']) {
            $form_title = get_lang('Edit evaluation');
        }

        $this->addHeader($form_title);
        $this->addElement('hidden', 'hid_user_id');
        $this->addElement('hidden', 'hid_course_code');

        $this->addText(
            'name',
            get_lang('Assessment'),
            true,
            [
                'maxlength' => '50',
                'id' => 'evaluation_title',
            ]
        );

        $cat_id = $this->evaluation_object->get_category_id();

        $session_id = api_get_session_id();
        $course_code = api_get_course_id();
        $all_categories = Category:: load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            false
        );

        if (1 == count($all_categories)) {
            $this->addElement('hidden', 'hid_category_id', $cat_id);
        } else {
            $select_gradebook = $this->addSelect(
                'hid_category_id',
                get_lang('Select assessment'),
                [],
                ['id' => 'hid_category_id']
            );
            $this->addRule('hid_category_id', get_lang('Required field'), 'nonzero');
            $default_weight = 0;
            if (!empty($all_categories)) {
                foreach ($all_categories as $my_cat) {
                    if ($my_cat->get_course_code() == api_get_course_id()) {
                        $grade_model_id = $my_cat->get_grade_model_id();
                        if (empty($grade_model_id)) {
                            if (0 == $my_cat->get_parent_id()) {
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

        $model = ExerciseLib::getCourseScoreModel();

        if ($edit) {
            if (empty($model)) {
                if (!$this->evaluation_object->has_results()) {
                    $this->addText(
                        'max',
                        get_lang('Maximum score'),
                        true,
                        [
                            'maxlength' => '5',
                        ]
                    );
                } else {
                    $this->addText(
                        'max',
                        [get_lang('Maximum score'), get_lang('Cannot change the score')],
                        false,
                        [
                            'maxlength' => '5',
                            'disabled' => 'disabled',
                        ]
                    );
                }
            } else {
                $class = '';
                foreach ($model['score_list'] as $item) {
                    $class = $item['css_class'];
                }
                $this->addText(
                    'max',
                    get_lang('Maximum score'),
                    false,
                    [
                        'maxlength' => '5',
                        'class' => $class,
                        'disabled' => 'disabled',
                    ]
                );

                $defaults['max'] = $item['max'];
                $this->setDefaults($defaults);
            }
        } else {
            if (empty($model)) {
                $this->addText(
                    'max',
                    get_lang('Maximum score'),
                    true,
                    [
                        'maxlength' => '5',
                    ]
                );
                $default_max = api_get_setting('gradebook_default_weight');
                $defaults['max'] = isset($default_max) ? $default_max : 100;
                $this->setDefaults($defaults);
            } else {
                $class = '';
                foreach ($model['score_list'] as $item) {
                    $class = $item['css_class'];
                }
                $this->addText(
                    'max',
                    get_lang('Maximum score'),
                    false,
                    [
                        'maxlength' => '5',
                        'class' => $class,
                        'disabled' => 'disabled',
                    ]
                );

                $defaults['max'] = $item['max'];
                $this->setDefaults($defaults);
            }
        }

        $this->addElement('textarea', 'description', get_lang('Description'));
        $this->addRule('hid_category_id', get_lang('Required field'), 'required');
        $this->addElement('checkbox', 'visible', null, get_lang('Visible'));
        $this->addRule('max', get_lang('Only numbers'), 'numeric');
        $this->addRule(
            'max',
            get_lang('Negative value'),
            'compare',
            '>=',
            'server',
            false,
            false,
            0
        );
        $setting = api_get_setting('tool_visible_by_default_at_creation');
        $visibility_default = 1;
        if (isset($setting['gradebook']) && 'false' == $setting['gradebook']) {
            $visibility_default = 0;
        }
        $this->setDefaults(['visible' => $visibility_default]);
    }

    /**
     * @param $id
     * @param $username
     * @param $lastname
     * @param $firstname
     *
     * @return string
     */
    private function build_stud_label($id, $username, $lastname, $firstname)
    {
        $opendocurl_start = '';
        $opendocurl_end = '';
        // evaluation's origin is a link
        if ($this->evaluation_object->get_category_id() < 0) {
            $link = LinkFactory::get_evaluation_link($this->evaluation_object->get_id());
            $doc_url = $link->get_view_url($id);
            if (null != $doc_url) {
                $opendocurl_start .= '<a href="'.$doc_url.'" target="_blank">';
                $opendocurl_end = '</a>';
            }
        }

        return $opendocurl_start.api_get_person_name($firstname, $lastname).' ('.$username.')'.$opendocurl_end;
    }
}
