<?php

/* For licensing terms, see /license.txt */

class MultipleAnswerDropdown extends Question
{
    public $typePicture = 'mcma_dropdown.png';
    public $explanationLangVar = 'Multiple Answer Dropdown';

    public $question_table_class = 'table table-striped table-hover';

    public function __construct()
    {
        parent::__construct();
        $this->type = MULTIPLE_ANSWER_DROPDOWN;
    }

    public function createForm(&$form, $exercise)
    {
        global $text;

        parent::createForm($form, $exercise);

        $objExe  = ChamiloSession::read('objExercise');
        $course  = api_get_course_info();
        $courseId = (int)($course['real_id'] ?? 0);

        $form->addTextarea(
            'list_text',
            [get_lang('Answer list'), get_lang('Enter a list of answers (one answer by line)')],
            ['rows' => 8]
        );
        $form->addFile(
            'list_file',
            ['', get_lang('Or select a CSV file with a list of answers')],
            ['accept' => 'text/csv']
        );

        $buttonGroup = [];

        if ($objExe->edit_exercise_in_lp ||
            (empty($this->exerciseList) && empty($objExe->iid))
        ) {
            $buttonGroup[] = $form->addButton(
                'submitQuestion',
                $text,
                'check',
                'primary',
                'default',
                null,
                ['id' => 'submit-question'],
                true
            );
        }

        $form->addGroup($buttonGroup);

        if (!empty($this->iid)) {
            // Load existing options bound to this question (pass course + exercise)
            $objAnswer  = new Answer((int) $this->iid, $courseId, $exercise, false);
            $optionData = array_column($objAnswer->getAnswers(), 'answer');

            $form->setDefaults(['list_text' => implode(PHP_EOL, $optionData)]);
        }
    }

    public function createAnswersForm($form)
    {
    }

    public function processCreation($form, $exercise)
    {
        $listFile = $form->getSubmitValue('list_file');
        $listText = $form->getSubmitValue('list_text');

        parent::processCreation($form, $exercise);

        $questionId = 0;
        if (!empty($this->iid)) {
            $questionId = (int) $this->iid;
        } elseif (property_exists($this, 'iId') && !empty($this->iId)) {
            $questionId = (int) $this->iId;
        } elseif (!empty($this->id)) {
            // Fallback in case your build exposes "id"
            $questionId = (int) $this->id;
        }

        // Safety net: if we still don't have a question id, abort with a clear message
        if ($questionId <= 0) {
            throw new \RuntimeException('Question id was not generated; cannot save dropdown options.');
        }

        // Build the lines array (either CSV first column, or textarea lines)
        $lines = [];
        if (is_array($listFile) && isset($listFile['error']) && (int) $listFile['error'] === UPLOAD_ERR_OK) {
            $lines = Import::csvColumnToArray($listFile['tmp_name']); // reads first column only
        } elseif (!empty($listText)) {
            $lines = explode("\n", $listText);
        }
        // Normalize: trim, drop empty
        $lines = array_values(array_filter(array_map('trim', $lines), static fn ($v) => $v !== ''));

        // Create Answer bound to this question + course + exercise
        $course   = api_get_course_info();
        $courseId = (int) ($course['real_id'] ?? 0);
        $objAnswer = new Answer($questionId, $courseId, $exercise, false);

        // Fill answers (dropdown typically has no "correct" by default)
        $pos = 1;
        foreach ($lines as $line) {
            $objAnswer->createAnswer($line, 0, '', 0, $pos);
            $pos++;
        }

        // 6) Persist answers
        $objAnswer->save();
    }

    /**
     * @param FormValidator $form
     * @param Exercise      $exercise
     *
     * @return void
     */
    public function processAnswersCreation($form, $exercise)
    {
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);

        $tableClass = (property_exists($this, 'question_table_class') && !empty($this->question_table_class))
            ? $this->question_table_class
            : 'table table-striped table-hover';

        $header .= '<table class="'.$tableClass.'"><thead><tr>';

        $header .= '<th class="text-center">'.get_lang('Your choice').'</th>';

        if ($exercise->showExpectedChoiceColumn()) {
            $header .= '<th class="text-center">'.get_lang('Expected choice').'</th>';
        }

        $header .= '<th>'.get_lang('Answer').'</th>';

        if ($exercise->showExpectedChoice()) {
            $header .= '<th class="text-center">'.get_lang('Status').'</th>';
        }

        $header .= '</tr></thead>';

        return $header;
    }
}
