<?php
/* For licensing terms, see /license.txt */

class Manifest10Validator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::manifest_validator1, $location);
    }
}
