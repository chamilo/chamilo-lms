<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

class BltiValidator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::BLTI_VALIDATOR13, $location);
    }
}
