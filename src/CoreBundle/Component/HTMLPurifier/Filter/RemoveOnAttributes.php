<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\HTMLPurifier\Filter;

use HTMLPurifier_Filter;

class RemoveOnAttributes extends HTMLPurifier_Filter
{
    /**
     * @var string
     */
    public $name = 'RemoveOnAttributes';

    public function preFilter($html, $config, $context): string
    {
        return self::filter($html);
    }

    public static function filter($html): string
    {
        // Strip null bytes before regex matching to prevent bypass via on\x00load patterns
        $html = str_replace("\0", '', $html);
        $pattern = '/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i';

        return preg_replace($pattern, '', $html);
    }
}
