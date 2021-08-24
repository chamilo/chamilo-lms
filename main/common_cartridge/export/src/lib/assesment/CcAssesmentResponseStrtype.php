<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentResponseStrtype extends CcResponseLidtype
{
    public function __construct()
    {
        $rtt = parent::__construct();
        $this->tagname = CcQtiTags::RESPONSE_STR;
    }
}
