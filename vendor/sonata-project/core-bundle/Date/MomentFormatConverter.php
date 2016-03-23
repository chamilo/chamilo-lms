<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Date;

/**
 * Handles Moment.js <-> PHP date format conversion.
 *
 * Inspired by https://github.com/fightbulc/moment.php/blob/master/src/Moment/CustomFormats/MomentJs.php
 *
 *
 * @author Hugo Briand <briand@ekino.com>
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class MomentFormatConverter
{
    /**
     * @var array This defines the mapping between PHP ICU date format (key) and moment.js date format (value)
     *            For ICU formats see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax
     *            For Moment formats see http://momentjs.com/docs/#/displaying/format/
     */
    private static $formatConvertRules = array(
        // year
        'yyyy' => 'YYYY', 'yy' => 'YY', 'y' => 'YYYY',
        // month
        // 'MMMM'=>'MMMM', 'MMM'=>'MMM', 'MM'=>'MM',
        // day
        'dd' => 'DD', 'd' => 'D',
        // hour
        // 'HH'=>'HH', 'H'=>'H', 'h'=>'h', 'hh'=>'hh',
        // am/pm
        // 'a' => 'a',
        // minute
        // 'mm'=>'mm', 'm'=>'m',
        // second
        // 'ss'=>'ss', 's'=>'s',
        // day of week
        'EE' => 'ddd', 'EEEEEE' => 'dd',
        // timezone
        'ZZZZZ' => 'Z', 'ZZZ' => 'ZZ',
        // letter 'T'
        '\'T\'' => 'T',
    );

    /**
     * Returns associated moment.js format.
     *
     * @param $format PHP Date format
     *
     * @return string Moment.js date format
     */
    public function convert($format)
    {
        return strtr($format, self::$formatConvertRules);
    }
}
