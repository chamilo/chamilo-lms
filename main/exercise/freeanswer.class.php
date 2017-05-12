<?php
/* For licensing terms, see /license.txt */

/**
 * File containing the FreeAnswer class.
 * This class allows to instantiate an object of type FREE_ANSWER,
 * extending the class question
 * @package chamilo.exercise
 * @author Eric Marguin
 */
class FreeAnswer extends Question
{
    public static $typePicture = 'open_answer.png';
    public static $explanationLangVar = 'FreeAnswer';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = FREE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * @inheritdoc
     */
    public function createAnswersForm($form)
    {
        $form->addElement('text', 'weighting', get_lang('Weighting'));
        global $text, $class;
        // setting the save button here and not in the question class.php
        $form->addButtonSave($text, 'submitQuestion');
        if (!empty($this->id)) {
            $form->setDefaults(array('weighting' => float_format($this->weighting, 1)));
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults(array('weighting' => '10'));
            }
        }
    }

    /**
     * abstract function which creates the form to create/edit the answers of the question
     * @param FormValidator $form
     */
    function processAnswersCreation($form)
    {
        $this->weighting = $form->getSubmitValue('weighting');
        $this->save();
    }

    function return_header($feedback_type = null, $counter = null, $score = null)
    {
        if (!empty($score['comments']) || $score['score'] > 0) {
            $score['revised'] = true;
        } else {
            $score['revised'] = false;
        }
        $header = parent::return_header($feedback_type, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'" >
        <tr>
        <th>' . get_lang("Answer").'</th>
        </tr>';

        return $header;
    }
}
