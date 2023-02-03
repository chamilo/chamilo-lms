<?php
/* For licensing terms, see /license.txt */

/**
 * FillBlanksCombination.
 */
class FillBlanksCombination extends FillBlanks
{
    public $typePicture = 'fill_in_blanks_co.png';
    public $explanationLangVar = 'FillBlanksCombination';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = FILL_IN_BLANKS_COMBINATION;
        $this->isContent = $this->getIsContent();
    }
}
