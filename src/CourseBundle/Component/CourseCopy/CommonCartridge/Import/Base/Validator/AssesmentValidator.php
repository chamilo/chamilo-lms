<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

class AssesmentValidator extends CcValidateType
{
    public function __construct(string $location)
    {
        // Keep the constant name used across the codebase (typo preserved for BC).
        parent::__construct(self::ASSESMENT_VALIDATOR13, $location);
    }
}

/*
 * Optional: provide a correctly spelled alias so both names can be used.
 * This avoids breaking external code expecting "AssessmentValidator".
 */
if (!class_exists(__NAMESPACE__.'\AssessmentValidator', false)) {
    class_alias(__NAMESPACE__.'\AssesmentValidator', __NAMESPACE__.'\AssessmentValidator');
}
