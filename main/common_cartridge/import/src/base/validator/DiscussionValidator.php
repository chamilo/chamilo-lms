<?php
/* For licensing terms, see /license.txt */

class DiscussionValidator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::discussion_validator13, $location);
    }
}
