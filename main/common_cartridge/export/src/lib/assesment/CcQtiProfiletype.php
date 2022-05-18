<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

abstract class CcQtiProfiletype
{
    public const MULTIPLE_CHOICE = 'cc.multiple_choice.v0p1';
    public const MULTIPLE_RESPONSE = 'cc.multiple_response.v0p1';
    public const TRUE_FALSE = 'cc.true_false.v0p1';
    public const FIELD_ENTRY = 'cc.fib.v0p1';
    public const PATTERN_MATCH = 'cc.pattern_match.v0p1';
    public const ESSAY = 'cc.essay.v0p1';

    /**
     * validates a profile value.
     *
     * @param string $value
     *
     * @return bool
     */
    public static function valid($value)
    {
        static $verificationValues = [self::ESSAY,
            self::FIELD_ENTRY,
            self::MULTIPLE_CHOICE,
            self::MULTIPLE_RESPONSE,
            self::PATTERN_MATCH,
            self::TRUE_FALSE,
        ];

        return in_array($value, $verificationValues);
    }
}
