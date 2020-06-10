<?php

/* For licensing terms, see /license.txt */

/**
 *  Class MultipleAnswerCombinationTrueFalse.
 *
 *  This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *  extending the class question
 *
 * @author Eric Marguin
 */
class MultipleAnswerCombinationTrueFalse extends MultipleAnswerCombination
{
    public $typePicture = 'mcmaco.png';
    public $explanationLangVar = 'MultipleAnswerCombinationTrueFalse';
    public $options;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE;
        $this->isContent = $this->getIsContent();
        $this->options = [
            '1' => get_lang('True'),
            '0' => get_lang('False'),
            '2' => get_lang('DontKnow'),
        ];
    }
}
