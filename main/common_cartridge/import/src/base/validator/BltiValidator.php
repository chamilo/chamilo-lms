<?php
/* For licensing terms, see /license.txt */

class BltiValidator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::blti_validator13, $location);
    }
}
