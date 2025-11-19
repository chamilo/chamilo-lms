<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

class CcAssesmentResponseStrtype extends CcResponseLidtype
{
    public function __construct()
    {
        $rtt = parent::__construct();
        $this->tagname = CcQtiTags::RESPONSE_STR;
    }
}
