<?php

/* For licensing terms, see /license.txt */

class MultipleAnswerDropdownGlobal extends MultipleAnswerDropdown
{
    public $typePicture = 'mcma_dropdown_global.png';
    public $explanationLangVar = 'MultipleAnswerDropdownGlobal';

    public function __construct()
    {
        parent::__construct();

        $this->type = MULTIPLE_ANSWER_DROPDOWN_GLOBAL;
    }
}
