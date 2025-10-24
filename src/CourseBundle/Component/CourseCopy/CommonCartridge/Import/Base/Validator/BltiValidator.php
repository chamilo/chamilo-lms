<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

class BltiValidator extends CcValidateType
{
    public function __construct(string $location)
    {
        parent::__construct(self::BLTI_VALIDATOR13, $location);
    }
}
