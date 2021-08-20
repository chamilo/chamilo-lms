<?php
/* For licensing terms, see /license.txt */

class ManifestValidator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::manifest_validator13, $location);
    }
}
