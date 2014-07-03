<?php

namespace JMS\SecurityExtraBundle\Security\Util;

/**
 * String utility functions.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class String
{
    final private function __construct() {}

    /**
     * Whether two strings are equal.
     *
     * This function uses a constant-time algorithm to compare the strings.
     *
     * @param  string  $str1
     * @param  string  $str2
     * @return Boolean
     */
    public static function equals($str1, $str2)
    {
        if (strlen($str1) !== $c = strlen($str2)) {
            return false;
        }

        $result = 0;
        for ($i=0; $i<$c; $i++) {
            $result |= ord($str1[$i]) ^ ord($str2[$i]);
        }

        return 0 === $result;
    }
}
