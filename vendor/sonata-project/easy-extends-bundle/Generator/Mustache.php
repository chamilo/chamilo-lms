<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Generator;

class Mustache
{
    /**
     * @param       $string
     * @param array $parameters
     *
     * @return mixed
     */
    public static function replace($string, array $parameters)
    {
        $replacer = function ($match) use ($parameters) {
            return isset($parameters[$match[1]]) ? $parameters[$match[1]] : $match[0];
        };

        return preg_replace_callback('/{{\s*(.+?)\s*}}/', $replacer, $string);
    }

    /**
     * @param string $file
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function replaceFromFile($file, array $parameters)
    {
        return self::replace(file_get_contents($file), $parameters);
    }
}
