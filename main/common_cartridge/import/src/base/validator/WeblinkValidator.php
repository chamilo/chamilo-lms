<?php
/* For licensing terms, see /license.txt */

class WeblinkValidator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::weblink_validator13, $location);
    }
}
