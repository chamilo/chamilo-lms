<?php
/* For licensing terms, see /license.txt */

class CcAssignmentConditionvarVarsubstringtype extends CcAssignmentConditionvarVarequaltype
{
    public function __construct($value)
    {
        parent::__construct($value);
        $this->tagname = CcQtiTags::varsubstring;
    }
}
