<?php

/* For licensing terms, see /license.txt */

class MultipleAnswerDropdown extends Question
{
    public $typePicture = 'mcma_dropdown.png';
    public $explanationLangVar = 'MultipleAnswerDropdown';

    public function __construct()
    {
        parent::__construct();

        $this->type = MULTIPLE_ANSWER_DROPDOWN;
    }

    public function createForm(&$form, $exercise)
    {
        global $text;

        parent::createForm($form, $exercise);

        $objExe = ChamiloSession::read('objExercise');

        $form->addTextarea(
            'list_text',
            [get_lang('AnswerList'), get_lang('EnterListOfAnswersOneAnswerByLine')],
            ['rows' => 8]
        );
        $form->addFile(
            'list_file',
            ['', get_lang('OrSelectCsvFileWithListOfAnswers')],
            ['accept' => 'text/csv']
        );

        $buttonGroup = [];

        if ($objExe->edit_exercise_in_lp == true ||
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
            $objAnswer = new Answer($this->iid, 0, $exercise, false);
            $optionData = array_column(
                $objAnswer->getAnswers(),
                'answer'
            );

            $form->setDefaults(
                ['list_text' => implode(PHP_EOL, $optionData)]
            );
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

        $lines = [];

        if (UPLOAD_ERR_OK === (int) $listFile['error']) {
            $lines = Import::csvColumnToArray($listFile['tmp_name']);
        } elseif (!empty($listText)) {
            $lines = explode("\n", $listText);
        }

        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        $objAnswer = new Answer($this->iid);

        $i = 1;

        foreach ($lines as $line) {
            $isCorrect = 0;

            if (isset($objAnswer->correct[$i])) {
                $isCorrect = (int) $objAnswer->correct[$i];
            }

            $objAnswer->createAnswer($line, $isCorrect, '', $objAnswer->weighting[$i] ?? 0, $i++);
        }

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

        $header .= '<table class="'.$this->question_table_class.'"><thead><tr>';

        $header .= '<th class="text-center">'.get_lang('Choice').'</th>';

        if ($exercise->showExpectedChoiceColumn()) {
            $header .= '<th class="text-center">'.get_lang('ExpectedChoice').'</th>';
        }

        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<th class="text-center">'.get_lang('Status').'</th>';
        }

        $header .= '</tr></thead>';

        return $header;
    }
}
