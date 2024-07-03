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
     * @param array $surveyData
     */
    public function addParentMenu($formData, FormValidator $form, $surveyData)
    {
        $surveyId = $surveyData['survey_id'];
        $questionId = isset($formData['question_id']) ? $formData['question_id'] : 0;
        $parentId = isset($formData['parent_id']) ? $formData['parent_id'] : 0;
        $optionId = isset($formData['parent_option_id']) ? $formData['parent_option_id'] : 0;
        $questions = SurveyManager::get_questions($surveyId);

        $newQuestionList = [];
        $allowTypes = ['yesno', 'multiplechoice', 'multipleresponse'];
        foreach ($questions as $question) {
            if (in_array($question['type'], $allowTypes)) {
                $newQuestionList[$question['sort']] = $question;
            }
        }
        ksort($newQuestionList);

        $options = [];
        foreach ($newQuestionList as $question) {
            if (!empty($questionId)) {
                if ($question['question_id'] == $questionId) {
                    break;
                }
            }
            $options[$question['question_id']] = strip_tags($question['question']);
        }
        $form->addSelect(
            'parent_id',
            get_lang('Parent'),
            $options,
            ['id' => 'parent_id', 'placeholder' => get_lang('SelectAnOption')]
        );
        $url = api_get_path(WEB_AJAX_PATH).
            'survey.ajax.php?'.api_get_cidreq().'&a=load_question_options&survey_id='.$surveyId;
        $form->addHtml('
            <script>
                $(function() {
                    $("#parent_id").on("change", function() {
                        var questionId = $(this).val()
                        var $select = $("#parent_option_id");
                        $select.empty();

                        if (questionId === "") {
                              $("#option_list").hide();
                        } else {
                            $.getJSON({
                                url: "'.$url.'" + "&question_id=" + questionId,
                                success: function(data) {
                                    $("#option_list").show();
                                    $.each(data, function(key, value) {
                                        $("<option>").val(key).text(value).appendTo($select);
                                    });
                                }
                            });
                        }
                    });
                });
            </script>
        ');

        $style = 'display:none';
        $options = [];
        if (!empty($optionId) && !empty($parentId)) {
            $parentData = SurveyManager::get_question($parentId);
            $style = '';
            foreach ($parentData['answer_data'] as $answer) {
                $options[$answer['iid']] = strip_tags($answer['data']);
            }
        }

        $form->addHtml('<div id="option_list" style="'.$style.'">');
        $form->addSelect(
            'parent_option_id',
            get_lang('Option'),
            $options,
            ['id' => 'parent_option_id', 'disable_js' => true]
        );
        $form->addHtml('</div>');
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
            case 'selectivedisplay':
                return new ch_selectivedisplay();
            case 'multiplechoiceother':
                return new ch_multiplechoiceother();
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

        $actionHeader = get_lang('EditQuestion').': ';
        if ($action === 'add') {
            $actionHeader = get_lang('AddQuestion').': ';
        }

        $questionComment = '';
        $allowParent = false;
        switch ($type) {
            case 'open':
                $toolName = get_lang('Open');
                $questionComment = get_lang('QuestionTags');
                $allowParent = true;
                break;
            case 'yesno':
                $toolName = get_lang('YesNo');
                $allowParent = true;
                break;
            case 'multiplechoice':
                $toolName = get_lang('UniqueSelect');
                $allowParent = true;
                break;
            case 'multipleresponse':
                $toolName = get_lang('MultipleResponse');
                $allowParent = true;
                break;
            case 'selectivedisplay':
                $toolName = get_lang('SurveyQuestionSelectiveDisplay');
                $questionComment = get_lang('SurveyQuestionSelectiveDisplayComment');
                $allowParent = true;
                break;
            case 'multiplechoiceother':
                $toolName = get_lang('SurveyQuestionMultipleChoiceWithOther');
                $questionComment = get_lang('SurveyQuestionMultipleChoiceWithOtherComment');
                $allowParent = true;
                break;
            case 'pagebreak':
                $toolName = get_lang(api_ucfirst($type));
                $allowParent = false;
                break;
            default:
                $toolName = get_lang(api_ucfirst($type));
                $allowParent = true;
                break;
        }

        if (false === api_get_configuration_value('survey_question_dependency')) {
            $allowParent = false;
        }

        $icon = Display::return_icon(
                SurveyManager::icon_question($type),
                $toolName,
                ['align' => 'middle', 'height' => '22px']
            ).' ';

        $toolName = $icon.$actionHeader.$toolName;
        $sharedQuestionId = isset($formData['shared_question_id']) ? $formData['shared_question_id'] : null;

        $url = api_get_self().'?action='.$action.'&type='.$type.'&survey_id='.$surveyId.'&question_id='.$questionId.'&'.api_get_cidreq();
        $form = new FormValidator('question_form', 'post', $url);
        $form->addHeader($toolName);
        if (!empty($questionComment)) {
            $form->addHtml(Display::return_message($questionComment, 'info', false));
        }
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
            $form->addCheckBox('is_required', get_lang('IsMandatory'), get_lang('Yes'));
        }

        if ($allowParent) {
            $this->addParentMenu($formData, $form, $surveyData);
        }

        if (1 == $surveyData['survey_type']) {
            $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
            $sql = 'SELECT id, name FROM '.$table_survey_question_group.'
                    WHERE survey_id = '.$surveyId.'
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

            $this->html .= '<tr><td colspan="">
			<fieldset style="border:1px solid black">
			    <legend>'.get_lang('Condition').'</legend>
			    <b>'.get_lang('Primary').'</b><br />
			    <input type="radio" name="choose" value="1" '.(($formData['choose'] == 1) ? 'checked' : '').'>
			    <select name="assigned">'.$grouplist.'</select><br />';
            $this->html .= '
			<b>'.get_lang('Secondary').'</b><br />
			    <input type="radio" name="choose" value="2" '.(($formData['choose'] == 2) ? 'checked' : '').'>
			    <select name="assigned1">'.$grouplist1.'</select>
                <select name="assigned2">'.$grouplist2.'</select>
            </fieldset><br />';
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
             * Prevent the edition of already-answered questions to avoid
             * inconsistent answers. Use the configuration option
             * survey_allow_answered_question_edit to change this behaviour.
             */
            $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;
            $answersChecker = SurveyUtil::checkIfSurveyHasAnswers($surveyId);
            $allowQuestionEdit = api_get_configuration_value('survey_allow_answered_question_edit') == true;
            if ($allowQuestionEdit or !$answersChecker) {
                $this->buttonList[] = $this->getForm()->addButtonUpdate(get_lang('ModifyQuestionSurvey'), 'save', true);
            } else {
                $this->getForm()->addHtml('
                    <div class="form-group">
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-8">
                            <div class="alert alert-info">'.
                            get_lang('YouCantNotEditThisQuestionBecauseAlreadyExistAnswers').'</div>
                        </div>
                        <div class="col-sm-2"></div>
                    </div>
                ');
            }
        } else {
            $this->buttonList[] = $this->getForm()->addButtonSave(get_lang('CreateQuestionSurvey'), 'save', true);
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
         * Deleting a specific answer is only saved in the session until the
         * "Save question" button is pressed. This means all options are kept
         * in the survey_question_option table until the question is saved.
         */
        if (isset($_POST['delete_answer'])) {
            $deleted = false;
            foreach ($_POST['delete_answer'] as $key => &$value) {
                $deleted = $key;
                $counter--;
                Session::write('answer_count', $counter);
            }

            $newAnswers = [];
            $newAnswersId = [];
            foreach ($formData['answers'] as $key => &$value) {
                if ($key > $deleted) {
                    // swap with previous (deleted) option slot
                    $newAnswers[$key - 1] = $formData['answers'][$key];
                    $newAnswersId[$key - 1] = $formData['answersid'][$key];
                    unset($formData['answers'][$key]);
                    unset($formData['answersid'][$key]);
                } elseif ($key === $deleted) {
                    // delete option
                    unset($formData['answers'][$deleted]);
                    unset($formData['answersid'][$deleted]);
                } else {
                    // keep as is
                    $newAnswers[$key] = $value;
                    $newAnswersId[$key] = $formData['answersid'][$key];
                }
            }
            unset($formData['answers']);
            unset($formData['answersid']);
            $formData['answers'] = $newAnswers;
            $formData['answersid'] = $newAnswersId;
        }

        // Adding an answer
        if (isset($_POST['buttons']) && isset($_POST['buttons']['add_answer'])) {
            if (isset($_REQUEST['type']) && 'multiplechoiceother' === $_REQUEST['type'] && $counter > 2) {
                $counter--;
            }
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
                    unset($formData['answersid'][$index]);
                }
            }
        }

        if (!isset($_POST['delete_answer'])) {
            // Make sure we have an array of answers
            if (!isset($formData['answers'])) {
                $formData['answers'] = [];
            }
            // Check if no deleted answer remains at the end of the answers
            // array and add empty answers if the array is too short
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
    public function save($surveyData, $formData, $dataFromDatabase = [])
    {
        // Saving a question
        if (isset($_POST['buttons']) && isset($_POST['buttons']['save'])) {
            Session::erase('answer_count');
            Session::erase('answer_list');
            $message = SurveyManager::save_question($surveyData, $formData, true, $dataFromDatabase);

            if ($message === 'QuestionAdded' || $message === 'QuestionUpdated') {
                $url = api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.intval($_GET['survey_id']).'&message='.$message.'&'.api_get_cidreq();
                header('Location: '.$url);
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
            get_lang('RemoveAnswer'),
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
            get_lang('AddAnswer'),
            'plus',
            'default'
        );
    }

    /**
     * Get the JS for questions that can depend on a previous question
     * (and that hides those questions until something changes in the previous
     * question).
     *
     * @return string HTML code
     */
    public static function getJs()
    {
        return '
            <style>
            .with_parent {
                display: none;
            }
            </style>
            <script>
            $(function() {
            });
            </script>';
    }

    /**
     * Get the question parents recursively, if any. This function depends on
     * the existence of a parent_id field, which depends on the
     * 'survey_question_dependency' setting and its corresponding SQL
     * requirements.
     *
     * @param int   $questionId The c_survey_question.question.id
     * @param array $list       An array of parents to be extended by this method
     *
     * @return array The completed array of parents
     */
    public static function getParents($questionId, $list = [])
    {
        if (true !== api_get_configuration_value('survey_question_dependency')) {
            return $list;
        }
        $courseId = api_get_course_int_id();
        $questionId = (int) $questionId;

        $table = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $sql = "SELECT parent_id FROM $table
                WHERE c_id = $courseId AND question_id = $questionId ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        if ($row && !empty($row['parent_id'])) {
            $list[] = $row['parent_id'];
            $list = self::getParents($row['parent_id'], $list);
        }

        return $list;
    }

    /**
     * Creates the JS code for the given parent question so that it shows
     * the children questions when a specific answer of the parent is selected.
     *
     * @param array $question An array with the question details
     *
     * @return string JS code to add to the HTML survey question page
     */
    public static function getQuestionJs($question)
    {
        if (empty($question)) {
            return '';
        }

        $list = self::getDependency($question);

        if (empty($list)) {
            return '';
        }

        $type = $question['type'];
        $questionId = $question['question_id'];
        $newList = [];
        foreach ($list as $child) {
            $childQuestionId = $child['question_id'];
            $optionId = $child['parent_option_id'];
            $newList[$optionId][] = $childQuestionId;
        }

        if ('multipleresponse' === $type) {
            $multiple = '';
            foreach ($newList as $optionId => $child) {
                $multiple .= '
                    $(\'input[name="question'.$questionId.'['.$optionId.']"]\').on("change", function() {
                        var isChecked= $(this).is(\':checked\');
                        var checkedValue = $(this).val();
                        if (isChecked) {
                            $.each(list, function(index, value) {
                                $(".with_parent_" + value).find("input").prop("checked", false);
                            });

                            var questionId = $(this).val();
                            var questionToShow = list[questionId];
                            $(".with_parent_" + questionToShow).show();
                        } else {
                            var checkedValue = list[checkedValue];
                        }
                    });
                ';
            }

            $js = '
            <script>
            $(function() {
                var list = '.json_encode($newList).';
                '.$multiple.'
            });
            </script>';

            return $js;
        }

        $js = '
            <script>
            $(function() {
                var list = '.json_encode($newList).';
                $("input[name=question'.$questionId.']").on("click", function() {
                    $.each(list, function(index, value) {
                         $.each(value, function(index, itemValue) {
                            $(".with_parent_" + itemValue).hide();
                            $(".with_parent_" + itemValue).find("input").prop("checked", false);
                            $(".with_parent_only_hide_" + itemValue).hide();
                        });
                    });

                    var questionId = $(this).val();
                    var questionToShowList = list[questionId];
                    $.each(questionToShowList, function(index, value) {
                        $(".with_parent_" + value).show();
                    });
                });
            });
            </script>';

        return $js;
    }

    /**
     * Returns the (children) questions that have the given question as parent.
     *
     * @param array $question An array describing the parent question
     *
     * @return array The questions that have the given question as parent
     */
    public static function getDependency($question)
    {
        if (true !== api_get_configuration_value('survey_question_dependency')) {
            return [];
        }
        $table = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $questionId = $question['question_id'];
        $courseId = api_get_course_int_id();

        // Getting the information of the question
        $sql = "SELECT * FROM $table
		        WHERE c_id = $courseId AND parent_id = $questionId ";
        $result = Database::query($sql);
        $row = Database::store_result($result, 'ASSOC');

        return $row;
    }

    /**
     * This method is not implemented at this level (returns null).
     *
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = [], $answers = [])
    {
        return null;
    }
}
