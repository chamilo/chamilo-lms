<?php
/* For licensing terms, see /license.txt */

/**
 * MatchingDraggableCombination.
 */
class MatchingDraggableCombination extends MatchingDraggable
{
    public $typePicture = 'matchingdrag_co.png';
    public $explanationLangVar = 'MatchingDraggableCombination';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MATCHING_DRAGGABLE_COMBINATION;
        $this->isContent = $this->getIsContent();
    }
}
