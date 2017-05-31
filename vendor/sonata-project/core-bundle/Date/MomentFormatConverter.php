<?php

/*
 * This file is part of the Sonata Project package.
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
        'EEEEEE' => 'dd', 'EEEE' => 'dddd', 'EE' => 'ddd',
        // timezone
        'ZZZZZ' => 'Z', 'ZZZ' => 'ZZ',
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
        $size = strlen($format);
        $foundOne = false;
        $output = '';
        //process the format string letter by letter
        for ($i = 0; $i < $size; ++$i) {
            //if finds a '
            if ($format[$i] === "'") {
                //if the next character are T' forming 'T', send a T to the
                //output
                if ($format[$i + 1] === 'T' && $format[$i + 2] === '\'') {
                    $output .= 'T';
                    $i += 2;
                } else {
                    //if it's no a 'T' then send whatever is inside the '' to
                //the output, but send it inside [] (useful for cases like
                //the brazilian translation that uses a 'de' in the date)
                    $output .= '[';
                    $temp = current(explode("'", substr($format, $i + 1)));
                    $output .= $temp;
                    $output .= ']';
                    $i += strlen($temp) + 1;
                }
            } else {
                //if no ' is found, then search all the rules, see if any of
                //them matchs
                $foundOne = false;
                foreach (self::$formatConvertRules as $key => $value) {
                    if (substr($format, $i, strlen($key)) === $key) {
                        $output .= $value;
                        $foundOne = true;
                        $i += strlen($key) - 1;
                        break;
                    }
                }
        //if no rule is matched, then just add the character to the
        //output
                if (!$foundOne) {
                    $output .= $format[$i];
                }
            }
        }

        return $output;
    }
}
