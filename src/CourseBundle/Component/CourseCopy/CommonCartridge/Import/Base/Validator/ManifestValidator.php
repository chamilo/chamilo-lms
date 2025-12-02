<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

class ManifestValidator extends CcValidateType
{
    public function __construct(string $location)
    {
        // CC 1.3 manifest validator by default
        parent::__construct(self::MANIFEST_VALIDATOR13, $location);
    }
}
