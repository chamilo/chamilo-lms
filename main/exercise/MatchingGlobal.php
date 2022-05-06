<?php
/* For licensing terms, see /license.txt */

/**
 * MatchingGlobal.
 */
class MatchingGlobal extends Matching
{
    public $typePicture = 'matching.png';
    public $explanationLangVar = 'MatchingGlobal';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MATCHING_GLOBAL;
        $this->isContent = $this->getIsContent();
    }
}
