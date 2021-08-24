<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_metadata_resource.php under GNU/GPL license */

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
