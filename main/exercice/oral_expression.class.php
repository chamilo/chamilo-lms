<?php
/* For licensing terms, see /license.txt */

/**
 * Class OralExpression
 * This class allows to instantiate an object of type FREE_ANSWER,
 * extending the class question
 * @author Eric Marguin
 *
 * @package chamilo.exercise
 */
class OralExpression extends Question
{
    static $typePicture = 'audio_question.png';
    static $explanationLangVar = 'OralExpression';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this -> type = ORAL_EXPRESSION;
        $this -> isContent = $this-> getIsContent();
    }

    /**
     * function which redefine Question::createAnswersForm
     * @param FormValidator $form
     */
    function createAnswersForm($form)
    {

        $form -> addElement('text','weighting', get_lang('Weighting'), array('class' => 'span1'));
        global $text, $class;
        // setting the save button here and not in the question class.php
        $form->addButtonSave($text, 'submitQuestion');
        if (!empty($this->id)) {
            $form -> setDefaults(array('weighting' => float_format($this->weighting, 1)));
        } else {
            if ($this -> isContent == 1) {
                $form -> setDefaults(array('weighting' => '10'));
            }
        }
    }

    /**
     * abstract function which creates the form to create / edit the answers of the question
     * @param the FormValidator $form
     */
    function processAnswersCreation($form)
    {
        $this->weighting = $form ->getSubmitValue('weighting');
        $this->save();
    }

    /**
     * @param null $feedback_type
     * @param null $counter
     * @param null $score
     * @return null|string
     */
    function return_header($feedback_type = null, $counter = null, $score = null)
    {
        $header = parent::return_header($feedback_type, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'">
            <tr>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th>'.get_lang("Answer").'</th>
            </tr>
            <tr>
                <th>&nbsp;</th>
            </tr>';

        return $header;
    }
}
