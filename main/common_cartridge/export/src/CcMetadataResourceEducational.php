<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_metadata_resource.php under GNU/GPL license */

/**
 * Metadata Resource Educational Type.
 */
class CcMetadataResourceEducational
{
    public $value = [];

    public function setValue($value)
    {
        $arr = [$value];
        $this->value[] = $arr;
    }
}
