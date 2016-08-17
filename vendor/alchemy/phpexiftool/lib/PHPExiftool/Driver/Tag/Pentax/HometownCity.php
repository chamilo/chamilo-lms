<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HometownCity extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'HometownCity';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Hometown City';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Pago Pago',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Honolulu',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Anchorage',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Vancouver',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'San Francisco',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Los Angeles',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Calgary',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Denver',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Mexico City',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Chicago',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Miami',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Toronto',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'New York',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Santiago',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Caracus',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Halifax',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Buenos Aires',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Sao Paulo',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Rio de Janeiro',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Madrid',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'London',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Paris',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Milan',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Rome',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Berlin',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Johannesburg',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Istanbul',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Cairo',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Jerusalem',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Moscow',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Jeddah',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Tehran',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Dubai',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Karachi',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Kabul',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Male',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Delhi',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Colombo',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Kathmandu',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Dacca',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Yangon',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Bangkok',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'Kuala Lumpur',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Vientiane',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Singapore',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Phnom Penh',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'Ho Chi Minh',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'Jakarta',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Hong Kong',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Perth',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Beijing',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Shanghai',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Manila',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Taipei',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'Seoul',
        ),
        55 => array(
            'Id' => 55,
            'Label' => 'Adelaide',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'Tokyo',
        ),
        57 => array(
            'Id' => 57,
            'Label' => 'Guam',
        ),
        58 => array(
            'Id' => 58,
            'Label' => 'Sydney',
        ),
        59 => array(
            'Id' => 59,
            'Label' => 'Noumea',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'Wellington',
        ),
        61 => array(
            'Id' => 61,
            'Label' => 'Auckland',
        ),
        62 => array(
            'Id' => 62,
            'Label' => 'Lima',
        ),
        63 => array(
            'Id' => 63,
            'Label' => 'Dakar',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Algiers',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Helsinki',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Athens',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Nairobi',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'Amsterdam',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'Stockholm',
        ),
        70 => array(
            'Id' => 70,
            'Label' => 'Lisbon',
        ),
        71 => array(
            'Id' => 71,
            'Label' => 'Copenhagen',
        ),
        72 => array(
            'Id' => 72,
            'Label' => 'Warsaw',
        ),
        73 => array(
            'Id' => 73,
            'Label' => 'Prague',
        ),
        74 => array(
            'Id' => 74,
            'Label' => 'Budapest',
        ),
    );

}
