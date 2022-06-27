<?php
/* For licensing terms, see /license.txt */

/**
 * HotSpotGlobal.
 */
class HotSpotGlobal extends HotSpot
{
    public $typePicture = 'hotspot_global.png';
    public $explanationLangVar = 'HotSpotGlobal';

    /**
     * HotSpot constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = HOT_SPOT_GLOBAL;
    }
}
