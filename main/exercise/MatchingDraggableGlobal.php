<?php
/* For licensing terms, see /license.txt */

/**
 * MatchingDraggableGlobal.
 */
class MatchingDraggableGlobal extends MatchingDraggable
{
    public $typePicture = 'matchingdrag_global.png';
    public $explanationLangVar = 'MatchingDraggableGlobal';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MATCHING_DRAGGABLE_GLOBAL;
        $this->isContent = $this->getIsContent();
    }
}
