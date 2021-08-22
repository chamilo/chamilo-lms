<?php
/* For licensing terms, see /license.txt */

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
