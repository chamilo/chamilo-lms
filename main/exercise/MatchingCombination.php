<?php
/* For licensing terms, see /license.txt */

/**
 * MatchingCombination.
 */
class MatchingCombination extends Matching
{
    public $typePicture = 'matching_co.png';
    public $explanationLangVar = 'MatchingCombination';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MATCHING_COMBINATION;
        $this->isContent = $this->getIsContent();
    }
}
