<?php
/* For licensing terms, see /license.txt */

/**
 * MultipleAnswerDropdownCombination.
 */
class MultipleAnswerDropdownCombination extends MultipleAnswerDropdown
{
    public $typePicture = 'mcma_dropdown_co.png';
    public $explanationLangVar = 'Multiple Answer Dropdown Combination';
    public $question_table_class = 'table table-striped table-hover';


    public function __construct()
    {
        parent::__construct();

        $this->type = MULTIPLE_ANSWER_DROPDOWN_COMBINATION;
    }
}
