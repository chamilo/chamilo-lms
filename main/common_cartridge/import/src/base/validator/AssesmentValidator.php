<?php
/* For licensing terms, see /license.txt */

class AssesmentValidator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::assesment_validator13, $location);
    }
}
