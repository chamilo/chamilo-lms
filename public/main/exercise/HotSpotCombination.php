<?php
/* For licensing terms, see /license.txt */

/**
 * HotSpotCombination.
 */
class HotSpotCombination extends HotSpot
{
    public $typePicture = 'hotspot_co.png';
    public $explanationLangVar = 'HotSpot Combination';

    /**
     * HotSpot constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = HOT_SPOT_COMBINATION;
    }
}
