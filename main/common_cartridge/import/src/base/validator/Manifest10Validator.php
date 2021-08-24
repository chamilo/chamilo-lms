<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

class Manifest10Validator extends CcValidateType
{
    public function __construct($location)
    {
        parent::__construct(self::MANIFEST_VALIDATOR1, $location);
    }
}
