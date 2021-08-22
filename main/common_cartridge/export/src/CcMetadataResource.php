<?php
/* For licensing terms, see /license.txt */

/**
 * Metadata Resource.
 */
class CcMetadataResource implements CcIMetadataResource
{
    public $arrayeducational = [];

    public function addMetadataResourceEducational($obj)
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        $this->arrayeducational['value'] = (!is_null($obj->value) ? $obj->value : null);
    }
}
