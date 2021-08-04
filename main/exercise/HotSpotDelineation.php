<?php

/* For licensing terms, see /license.txt */

/**
 * Class HotSpotDelineation.
 */
class HotSpotDelineation extends HotSpot
{
    public $typePicture = 'hotspot-delineation.png';
    public $explanationLangVar = 'HotspotDelineation';

    /**
     * HotSpotDelineation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = HOT_SPOT_DELINEATION;
    }
}
