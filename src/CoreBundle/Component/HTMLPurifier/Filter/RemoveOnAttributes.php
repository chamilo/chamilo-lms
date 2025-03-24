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
        $pattern = '/\s*on\w+=(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i';

        return preg_replace($pattern, '', $html);
    }
}
