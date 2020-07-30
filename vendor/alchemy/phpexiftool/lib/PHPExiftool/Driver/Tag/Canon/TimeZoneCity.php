<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TimeZoneCity extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'TimeZoneCity';

    protected $FullName = 'Canon::TimeInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Time';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Time Zone City';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Chatham Islands',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Wellington',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Solomon Islands',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Sydney',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Adelaide',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Tokyo',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Hong Kong',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Bangkok',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Yangon',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Dhaka',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Kathmandu',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Delhi',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Karachi',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Kabul',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Dubai',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Tehran',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Moscow',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Cairo',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Paris',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'London',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Azores',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Fernando de Noronha',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Sao Paulo',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Newfoundland',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Santiago',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Caracas',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'New York',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Chicago',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Denver',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Los Angeles',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Anchorage',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Honolulu',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Samoa',
        ),
        32766 => array(
            'Id' => 32766,
            'Label' => '(not set)',
        ),
    );

}
