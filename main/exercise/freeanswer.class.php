<?php

/* For licensing terms, see /license.txt */

/**
 * File containing the FreeAnswer class.
 * This class allows to instantiate an object of type FREE_ANSWER,
 * extending the class question.
 *
 * @author Eric Marguin
 */
class FreeAnswer extends Question
{
    public $typePicture = 'open_answer.png';
    public $explanationLangVar = 'FreeAnswer';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = FREE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        $form->addElement('text', 'weighting', get_lang('Weighting'));
        global $text;
        // setting the save button here and not in the question class.php
        $form->addButtonSave($text, 'submitQuestion');
        if (!empty($this->iid)) {
            $form->setDefaults(['weighting' => float_format($this->weighting, 1)]);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults(['weighting' => '10']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $this->weighting = $form->getSubmitValue('weighting');
        $this->save($exercise);
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $score['revised'] = $this->isQuestionWaitingReview($score);
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'" >
        <tr>
        <th>'.get_lang('Answer').'</th>
        </tr>';

        return $header;
    }
}
