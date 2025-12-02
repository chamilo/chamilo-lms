<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_metadata_resource.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

/**
 * Metadata Resource Educational Type.
 */
class CcMetadataResourceEducational
{
    public $value = [];

    public function setValue($value): void
    {
        $arr = [$value];
        $this->value[] = $arr;
    }
}
