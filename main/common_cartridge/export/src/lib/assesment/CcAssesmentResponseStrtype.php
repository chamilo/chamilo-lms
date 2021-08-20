<?php
/* For licensing terms, see /license.txt */

class CcAssesmentResponseStrtype extends CcResponseLidtype
{
    public function __construct()
    {
        $rtt = parent::__construct();
        $this->tagname = CcQtiTags::response_str;
    }
}
