<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

class WeblinkValidator extends CcValidateType
{
    public function __construct(string $location)
    {
        parent::__construct(self::WEBLINK_VALIDATOR13, $location);
    }
}
