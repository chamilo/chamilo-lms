<?php
/* For licensing terms, see /license.txt */

/**
 * MultipleAnswerDropdownCombination.
 */
class MultipleAnswerDropdownCombination extends MultipleAnswerDropdown
{
    public $typePicture = 'mcma_dropdown_co.png';
    public $explanationLangVar = 'MultipleAnswerDropdownCombination';

    public function __construct()
    {
        parent::__construct();

        $this->type = MULTIPLE_ANSWER_DROPDOWN_COMBINATION;
    }
}
