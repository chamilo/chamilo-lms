<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class survey_question.
 */
class survey_question
{
    public $buttonList = [];
    /** @var FormValidator */
    private $form;

    /**
     * @param FormValidator $form
     * @param array         $surveyData
     */
    public function addParentMenu(FormValidator $form, $surveyData)
    {
        $surveyId = $surveyData['survey_id'];
        $questions = SurveyManager::get_questions($surveyId);

        $options = [];
        foreach ($questions as $question) {
            $options[$question['question_id']] = strip_tags($question['question']);
        }
        $form->addSelect(
            'parent_id',
            get_lang('Parent'),
            $options,
            ['id' => 'parent_id', 'placeholder' => get_lang('Please select an option')]
        );
        $url = api_get_path(WEB_AJAX_PATH).'survey.ajax.php?'.api_get_cidreq();
        $form->addHtml('
            <script>
                $(function() {                    
                    $("#parent_id").on("change", function() {
                        var questionId = $(this).val()
                        var params = {
                            "a": "load_question_options",
                            "survey_id": "'.$surveyId.'",
                            "question_id": questionId,
                        };    
                            
                          $.ajax({
                            type: "GET",
                            url: "'.$url.'",
                            data: params,
                            async: false,
                            success: function(data) {
                                $("#parent_options").html(data);
                            }
                        });        
                        console.log(); 
                    });
                });
            </script>
        ');
        $form->addHtml('<div id="parent_options"></div>');
        $form->addHidden('option_id', 0);
    }

    /**
     * @param string $type
     *
     * @return survey_question
     */
    public static function createQuestion($type)
    {
        switch ($type) {
            case 'comment':
                return new ch_comment();
            case 'dropdown':
                return new ch_dropdown();
            case 'multiplechoice':
                return new ch_multiplechoice();
            case 'multipleresponse':
                return new ch_multipleresponse();
            case 'open':
                return new ch_open();
            case 'pagebreak':
                return new ch_pagebreak();
            case 'percentage':
                return new ch_percentage();
            case 'personality':
                return new ch_personality();
            case 'score':
                return new ch_score();
            case 'yesno':
                return new ch_yesno();
            default:
                api_not_allowed(true);
                break;
        }
    }

    /**
     * Generic part of any survey question: the question field.
     *
     * @param array $surveyData
     * @param array $formData
     *
     * @return FormValidator
     */
    public function createForm($surveyData, $formData)
    {
        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
        $questionId = isset($_GET['question_id']) ? (int) $_GET['question_id'] : null;
        $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : null;
        $type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : null;

        $toolName = Display::return_icon(
            SurveyManager::icon_question($type),
            get_lang(ucfirst($type)),
            ['align' => 'middle', 'height' => '22px']
        ).' ';

        if ($action == 'add') {
            $toolName .= get_lang('Add a question').': ';
        } elseif ($action == 'edit') {
            $toolName .= get_lang('Edit question').': ';
        }

        switch ($_GET['type']) {
            case 'yesno':
                $toolName .= get_lang('Yes / No');
                break;
            case 'multiplechoice':
                $toolName .= get_lang('Multiple choice');
                break;
            case 'multipleresponse':
                $toolName .= get_lang('Multiple answers');
                break;
            default:
                $toolName .= get_lang(api_ucfirst($type));
        }

        $sharedQuestionId = isset($formData['shared_question_id']) ? $formData['shared_question_id'] : null;

        $url = api_get_self().'?action='.$action.'&type='.$type.'&survey_id='.$surveyId.'&question_id='.$questionId.'&'.api_get_cidreq();
        $form = new FormValidator('question_form', 'post', $url);
        $form->addHeader($toolName);
        $form->addHidden('survey_id', $surveyId);
        $form->addHidden('question_id', $questionId);
        $form->addHidden('shared_question_id', Security::remove_XSS($sharedQuestionId));
        $form->addHidden('type', $type);

        $config = [
            'ToolbarSet' => 'SurveyQuestion',
            'Width' => '100%',
            'Height' => '120',
        ];
        $form->addHtmlEditor(
            'question',
            get_lang('Question'),
            true,
            false,
            $config
        );

        if (api_get_configuration_value('allow_required_survey_questions') &&
            in_array($_GET['type'], ['yesno', 'multiplechoice'])) {
            $form->addCheckBox('is_required', get_lang('Mandatory?'), get_lang('Yes'));
        }

        // When survey type = 1??
        if ($surveyData['survey_type'] == 1) {
            $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
            $sql = 'SELECT id,name FROM '.$table_survey_question_group.'
                    WHERE survey_id = '.(int) $_GET['survey_id'].'
                    ORDER BY name';
            $rs = Database::query($sql);
            $glist = null;
            while ($row = Database::fetch_array($rs, 'NUM')) {
                $glist .= '<option value="'.$row[0].'" >'.$row[1].'</option>';
            }

            $grouplist = $grouplist1 = $grouplist2 = $glist;
            if (!empty($formData['assigned'])) {
                $grouplist = str_replace(
                    '<option value="'.$formData['assigned'].'"',
                    '<option value="'.$formData['assigned'].'" selected',
                    $glist
                );
            }

            if (!empty($formData['assigned1'])) {
                $grouplist1 = str_replace(
                    '<option value="'.$formData['assigned1'].'"',
                    '<option value="'.$formData['assigned1'].'" selected',
                    $glist
                );
            }

            if (!empty($formData['assigned2'])) {
                $grouplist2 = str_replace(
                    '<option value="'.$formData['assigned2'].'"',
                    '<option value="'.$formData['assigned2'].'" selected',
                    $glist
                );
            }

            $this->html .= '	<tr><td colspan="">
			<fieldset style="border:1px solid black"><legend>'.get_lang('Condition').'</legend>

			<b>'.get_lang('Primary').'</b><br />
			'.'<input type="radio" name="choose" value="1" '.(($formData['choose'] == 1) ? 'checked' : '').
                '><select name="assigned">'.$grouplist.'</select><br />';

            $this->html .= '
			<b>'.get_lang('Secondary').'</b><br />
			'.'<input type="radio" name="choose" value="2" '.(($formData['choose'] == 2) ? 'checked' : '').
                '><select name="assigned1">'.$grouplist1.'</select> '.
                '<select name="assigned2">'.$grouplist2.'</select>'
                .'</fieldset><br />';
        }

        $this->setForm($form);

        return $form;
    }

    /**
     * Adds submit button.
     */
    public function renderForm()
    {
        if (isset($_GET['question_id']) && !empty($_GET['question_id'])) {
            /**
             * Check if survey has answers first before update it, this is because if you update it, the question
             * options will delete and re-insert in database loosing the iid and question_id to verify the correct answers.
             */
            $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;
            $answersChecker = SurveyUtil::checkIfSurveyHasAnswers($surveyId);
            if (!$answersChecker) {
                $this->buttonList[] = $this->getForm()->addButtonUpdate(get_lang('Edit question'), 'save', true);
            } else {
                $this->getForm()->addHtml('
                    <div class="form-group">
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-8">
                            <div class="alert alert-info">'.get_lang('You can\'t edit this question because answers by students have already been registered').'</div>
                        </div>
                        <div class="col-sm-2"></div>
                    </div>
                ');
            }
        } else {
            $this->buttonList[] = $this->getForm()->addButtonSave(get_lang('Create question'), 'save', true);
        }

        $this->getForm()->addGroup($this->buttonList, 'buttons');
    }

    /**
     * @return FormValidator
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param FormValidator $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * @param array $formData
     *
     * @return mixed
     */
    public function preSave($formData)
    {
        $counter = Session::read('answer_count');
        $answerList = Session::read('answer_list');

        if (empty($answerList)) {
            $answerList = isset($formData['answers']) ? $formData['answers'] : [];
            Session::write('answer_list', $answerList);
        }

        if (isset($_POST['answers'])) {
            $formData['answers'] = $_POST['answers'];
        }

        if (empty($counter)) {
            $counter = count($answerList) - 1;
            Session::write('answer_count', $counter);
        }

        // Moving an answer up
        if (isset($_POST['move_up']) && $_POST['move_up']) {
            foreach ($_POST['move_up'] as $key => &$value) {
                $id1 = $key;
                $content1 = $formData['answers'][$id1];
                $id2 = $key - 1;
                $content2 = $formData['answers'][$id2];
                $formData['answers'][$id1] = $content2;
                $formData['answers'][$id2] = $content1;
            }
        }

        // Moving an answer down
        if (isset($_POST['move_down']) && $_POST['move_down']) {
            foreach ($_POST['move_down'] as $key => &$value) {
                $id1 = $key;
                $content1 = $formData['answers'][$id1];
                $id2 = $key + 1;
                $content2 = $formData['answers'][$id2];
                $formData['answers'][$id1] = $content2;
                $formData['answers'][$id2] = $content1;
            }
        }

        /**
         * This solution is a little bit strange but I could not find a different solution.
         */
        if (isset($_POST['delete_answer'])) {
            $deleted = false;
            foreach ($_POST['delete_answer'] as $key => &$value) {
                $deleted = $key;
                $counter--;
                Session::write('answer_count', $counter);
            }

            foreach ($formData['answers'] as $key => &$value) {
                if ($key > $deleted) {
                    $formData['answers'][$key - 1] = $formData['answers'][$key];
                    unset($formData['answers'][$key]);
                }
            }
        }

        // Adding an answer
        if (isset($_POST['buttons']) && isset($_POST['buttons']['add_answer'])) {
            $counter++;
            Session::write('answer_count', $counter);
        }

        // Removing an answer
        if (isset($_POST['buttons']) && isset($_POST['buttons']['remove_answer'])) {
            $counter--;
            Session::write('answer_count', $counter);
            foreach ($formData['answers'] as $index => &$data) {
                if ($index > $counter) {
                    unset($formData['answers'][$index]);
                }
            }
        }

        if (!isset($_POST['delete_answer'])) {
            if (isset($formData['answers'])) {
                foreach ($formData['answers'] as $index => $data) {
                    if ($index > $counter) {
                        unset($formData['answers'][$index]);
                    }
                }

                for ($i = 0; $i <= $counter; $i++) {
                    if (!isset($formData['answers'][$i])) {
                        $formData['answers'][$i] = '';
                    }
                }
            }
        }

        $formData['answers'] = isset($formData['answers']) ? $formData['answers'] : [];
        Session::write('answer_list', $formData['answers']);

        if (!isset($formData['is_required']) && api_get_configuration_value('survey_mark_question_as_required')) {
            $formData['is_required'] = true;
        }

        return $formData;
    }

    /**
     * @param array $surveyData
     * @param array $formData
     *
     * @return mixed
     */
    public function save($surveyData, $formData)
    {
        // Saving a question
        if (isset($_POST['buttons']) && isset($_POST['buttons']['save'])) {
            Session::erase('answer_count');
            Session::erase('answer_list');
            $message = SurveyManager::save_question(
                $surveyData,
                $formData
            );

            if ($message == 'QuestionAdded' || $message == 'QuestionUpdated') {
                header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.intval($_GET['survey_id']).'&message='.$message.'&'.api_get_cidreq());
                exit;
            }
        }

        return $formData;
    }

    /**
     * Adds two buttons. One to add an option, one to remove an option.
     *
     * @param array $data
     */
    public function addRemoveButtons($data)
    {
        $this->buttonList['remove_answer'] = $this->getForm()->createElement(
            'button',
            'remove_answer',
            get_lang('Remove option'),
            'minus',
            'default'
        );

        if (count($data['answers']) <= 2) {
            $this->buttonList['remove_answer']->updateAttributes(
                ['disabled' => 'disabled']
            );
        }

        $this->buttonList['add_answer'] = $this->getForm()->createElement(
            'button',
            'add_answer',
            get_lang('Add option'),
            'plus',
            'default'
        );
    }

    /**
     * @param FormValidator $form
     * @param array         $questionData
     * @param array         $answers
     */
    public function render(FormValidator $form, $questionData = [], $answers = [])
    {
        return null;
    }
}
