<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class survey_question
 */
class survey_question
{
    /** @var FormValidator */
    private $form;

    /**
     * Generic part of any survey question: the question field
     * @param array $surveyData
     * @param array $formData
     *
     * @return FormValidator
     */
    public function create_form($surveyData, $formData)
    {
        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
        $questionId = isset($_GET['question_id']) ? intval($_GET['question_id']) : null;
        $surveyId = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : null;

        $toolName = Display::return_icon(
                survey_manager::icon_question(Security::remove_XSS($_GET['type'])),
                get_lang(ucfirst(Security::remove_XSS($_GET['type']))),
                array('align' => 'middle', 'height' => '22px')
        ).' ';

        if ($action == 'add') {
            $toolName .= get_lang('AddQuestion');
        }
        if ($action == 'edit') {
            $toolName .= get_lang('EditQuestion');
        }

        if ($_GET['type'] == 'yesno') {
            $toolName .= ': '.get_lang('YesNo');
        } else if ($_GET['type'] == 'multiplechoice') {
            $toolName .= ': '.get_lang('UniqueSelect');
        } else {
            $toolName .= ': '.get_lang(api_ucfirst(Security::remove_XSS($_GET['type'])));
        }

        $sharedQuestionId = isset($formData['shared_question_id']) ? $formData['shared_question_id'] : null;

        $url = api_get_self().'?action='.$action.'&type='.Security::remove_XSS($_GET['type']).'&survey_id='.$surveyId.'&question_id='.$questionId.'&'.api_get_cidreq();

        $form = new FormValidator('question_form', 'post', $url);
        $form->addHeader($toolName);
        $form->addHidden('survey_id', $surveyId);
        $form->addHidden('question_id', $questionId);
        $form->addHidden('shared_question_id', Security::remove_XSS($sharedQuestionId));
        $form->addHidden('type', Security::remove_XSS($_GET['type']));
        $form->addHidden('save_question', 1);

        $config = array('ToolbarSet' => 'SurveyQuestion', 'Width' => '100%', 'Height' => '120');
        $form->addHtmlEditor('question', get_lang('Question'), true, false, $config);

        // When survey type = 1??
        if ($surveyData['survey_type'] == 1) {
            $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
            $sql = 'SELECT id,name FROM '.$table_survey_question_group.'
                    WHERE survey_id = '.(int)$_GET['survey_id'].'
                    ORDER BY name';
            $rs = Database::query($sql);
            $glist = null;
            while ($row = Database::fetch_array($rs, 'NUM')) {
                $glist .= '<option value="'.$row[0].'" >'.$row[1].'</option>';
            }

            $grouplist = $grouplist1 = $grouplist2 = $glist;

            if (!empty($formData['assigned'])) {
                $grouplist = str_replace('<option value="'.$formData['assigned'].'"','<option value="'.$formData['assigned'].'" selected',$glist);
            }

            if (!empty($formData['assigned1'])) {
                $grouplist1 = str_replace('<option value="'.$formData['assigned1'].'"','<option value="'.$formData['assigned1'].'" selected',$glist);
            }

            if (!empty($formData['assigned2'])) {
                $grouplist2 = str_replace('<option value="'.$formData['assigned2'].'"','<option value="'.$formData['assigned2'].'" selected',$glist);
            }

            $this->html .= '	<tr><td colspan="">
			<fieldset style="border:1px solid black"><legend>'.get_lang('Condition').'</legend>

			<b>'.get_lang('Primary').'</b><br />
			'.'<input type="radio" name="choose" value="1" '.(($formData['choose'] == 1) ? 'checked' : '').
                '><select name="assigned">'.$grouplist.'</select><br />';

            $this->html .= '
			<b>'.get_lang('Secondary').'</b><br />
			'.'<input type="radio" name="choose" value="2" '.(($formData['choose']==2)?'checked':'').
                '><select name="assigned1">'.$grouplist1.'</select> '.
                '<select name="assigned2">'.$grouplist2.'</select>'
                .'</fieldset><br />';

            //$form->addRadio('choose', get_lang('Primary'));
            //$form->addRadio('choose', get_lang('Secondary'));
        }

        $form->setDefaults($formData);
        $this->setForm($form);

        return $form;
    }

    /**
     * Adds submit button
     *
     */
    public function render_form()
    {
        if (isset($_GET['question_id']) and !empty($_GET['question_id'])) {
            $icon = 'pencil';
            $text = get_lang('ModifyQuestionSurvey');
        } else {
            $icon = 'check';
            $text = get_lang('CreateQuestionSurvey');
        }

        $this->getForm()->addButton('save', $text, $icon);
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
     * @return mixed
     */
    public function preAction($formData)
    {
        // Moving an answer up
        if (isset($_POST['move_up']) && $_POST['move_up']) {
            foreach ($_POST['move_up'] as $key => & $value) {
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
            foreach ($_POST['move_down'] as $key => & $value) {
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
            foreach ($_POST['delete_answer'] as $key => & $value) {
                unset($formData['answers'][$key]);
                $deleted = $key;
            }
            foreach ($formData['answers'] as $key => & $value) {
                if ($key > $deleted) {
                    $formData['answers'][$key - 1] = $formData['answers'][$key];
                    unset($formData['answers'][$key]);
                }
            }
        }

        $counter = Session::read('answer_count');
        if (empty($counter)) {
            $counter = count($formData['answers']) - 1;
            Session::write('answer_count', $counter);
        }

        // Adding an answer
        if (isset($_POST['add_answer'])) {
            $counter++;
            Session::write('answer_count', $counter);
        }

        // Removing an answer
        if (isset($_POST['remove_answer'])) {
            $counter--;
            Session::write('answer_count', $counter);
            foreach ($formData['answers'] as $index => &$data) {
                if ($index > $counter) {
                    unset($formData['answers'][$index]);
                }
            }
        }

        foreach ($formData['answers'] as $index => $data) {
            if ($index > $counter) {
                unset($formData['answers'][$index]);
            }
        }

        for ($i = 0; $i < $counter; $i++) {
            if (!isset($formData['answers'][$i])) {
                $formData['answers'][$i] = '';
            }
        }

        return $formData;
    }

    /**
     * Handles the actions on a question and its answers
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public function handle_action($surveyData, $formData)
    {
        // Saving a question
        if (isset($_POST['save_question']) &&
            !isset($_POST['add_answer']) &&
            !isset($_POST['remove_answer']) &&
            !isset($_POST['delete_answer']) &&
            !isset($_POST['move_down']) &&
            !isset($_POST['move_up'])
        ) {
            Session::erase('answer_count');
            $message = survey_manager::save_question(
                $surveyData,
                $formData
            );

            if ($message == 'QuestionAdded' || $message == 'QuestionUpdated') {
                header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.intval($_GET['survey_id']).'&message='.$message.'&'.api_get_cidreq());
                exit;

            } else {
                /*if ($message == 'PleaseEnterAQuestion' || $message == 'PleasFillAllAnswer'|| $message == 'PleaseChooseACondition'|| $message == 'ChooseDifferentCategories') {
                    $_SESSION['temp_user_message'] = $formData['question'];
                    $_SESSION['temp_horizontalvertical'] = $formData['horizontalvertical'];
                    $_SESSION['temp_sys_message'] = $message;
                    $_SESSION['temp_answers'] = $formData['answers'];
                    $_SESSION['temp_values'] = $formData['values'];
                    header('location: '.api_get_path(WEB_CODE_PATH).'survey/question.php?'.api_get_cidreq().'&question_id='.intval($_GET['question_id']).'&survey_id='.intval($_GET['survey_id']).'&action='.Security::remove_XSS($_GET['action']).'&type='.Security::remove_XSS($_GET['type']).'');
                    exit;
                }*/
            }
        }

        return $formData;
    }

    /**
     * Adds two buttons. One to add an option, one to remove an option
     *
     * @param FormValidator $form
     * @param array $data
     *
     * @return FormValidator
     */
    public function add_remove_buttons($data)
    {
        $removeButton = $this->getForm()->addButton('remove_answer', get_lang('RemoveAnswer'), 'minus');
        if (count($data['answers']) <= 2) {
            $removeButton->updateAttributes(array('disabled' => 'disabled'));
        }
        $this->getForm()->addButton('add_answer', get_lang('AddAnswer'), 'plus');
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        return null;
    }
}

