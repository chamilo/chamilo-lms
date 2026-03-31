<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\HTMLPurifier\Filter;

use HTMLPurifier_Filter;

class RemoveOnAttributes extends HTMLPurifier_Filter
{
    public $name = 'RemoveOnAttributes';

    public function preFilter($html, $config, $context)
    {
        return self::filter($html);
    }

    public static function filter($html)
    {
        // Strip null bytes before regex matching to prevent bypass via on\x00load patterns
        $html = str_replace("\0", '', $html);
        $pattern = '/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i';

        return preg_replace($pattern, '', $html);
    }
}
