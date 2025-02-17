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
        $pattern = '/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i';

        return preg_replace($pattern, '', $html);
    }
}
