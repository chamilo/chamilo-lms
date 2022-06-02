<?php
/* For licensing terms, see /license.txt */

/**
 * FillBlanksGlobal.
 */
class FillBlanksGlobal extends FillBlanks
{
    public $typePicture = 'fill_in_blanks_global.png';
    public $explanationLangVar = 'FillBlanksGlobal';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = FILL_IN_BLANKS_GLOBAL;
        $this->isContent = $this->getIsContent();
    }
}
