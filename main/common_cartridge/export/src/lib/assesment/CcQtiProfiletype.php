<?php
/* For licensing terms, see /license.txt */

abstract class CcQtiProfiletype
{
    const multiple_choice = 'cc.multiple_choice.v0p1';
    const multiple_response = 'cc.multiple_response.v0p1';
    const true_false = 'cc.true_false.v0p1';
    const field_entry = 'cc.fib.v0p1';
    const pattern_match = 'cc.pattern_match.v0p1';
    const essay = 'cc.essay.v0p1';

    /**
     * validates a profile value.
     *
     * @param string $value
     *
     * @return bool
     */
    public static function valid($value)
    {
        static $verificationValues = [self::essay,
            self::field_entry,
            self::multiple_choice,
            self::multiple_response,
            self::pattern_match,
            self::true_false,
        ];

        return in_array($value, $verificationValues);
    }
}
