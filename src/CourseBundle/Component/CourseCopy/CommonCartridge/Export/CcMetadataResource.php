<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_metadata_resource.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataResource;
use Exception;

/**
 * Metadata Resource.
 */
class CcMetadataResource implements CcIMetadataResource
{
    public $arrayeducational = [];

    public function addMetadataResourceEducational($obj): void
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        $this->arrayeducational['value'] = (null !== $obj->value ? $obj->value : null);
    }
}
