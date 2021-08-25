<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalanceSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WhiteBalanceSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'White Balance Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 2,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 4,
            'Label' => 'Daylight',
        ),
        2 => array(
            'Id' => 5,
            'Label' => 'Fluorescent',
        ),
        3 => array(
            'Id' => 6,
            'Label' => 'Tungsten',
        ),
        4 => array(
            'Id' => 7,
            'Label' => 'Flash',
        ),
        5 => array(
            'Id' => 16,
            'Label' => 'Cloudy',
        ),
        6 => array(
            'Id' => 17,
            'Label' => 'Shade',
        ),
        7 => array(
            'Id' => 18,
            'Label' => 'Color Temperature/Color Filter',
        ),
        8 => array(
            'Id' => 32,
            'Label' => 'Custom 1',
        ),
        9 => array(
            'Id' => 33,
            'Label' => 'Custom 2',
        ),
        10 => array(
            'Id' => 34,
            'Label' => 'Custom 3',
        ),
        11 => array(
            'Id' => 16,
            'Label' => 'Auto (-3)',
        ),
        12 => array(
            'Id' => 17,
            'Label' => 'Auto (-2)',
        ),
        13 => array(
            'Id' => 18,
            'Label' => 'Auto (-1)',
        ),
        14 => array(
            'Id' => 19,
            'Label' => 'Auto (0)',
        ),
        15 => array(
            'Id' => 20,
            'Label' => 'Auto (+1)',
        ),
        16 => array(
            'Id' => 21,
            'Label' => 'Auto (+2)',
        ),
        17 => array(
            'Id' => 22,
            'Label' => 'Auto (+3)',
        ),
        18 => array(
            'Id' => 32,
            'Label' => 'Daylight (-3)',
        ),
        19 => array(
            'Id' => 33,
            'Label' => 'Daylight (-2)',
        ),
        20 => array(
            'Id' => 34,
            'Label' => 'Daylight (-1)',
        ),
        21 => array(
            'Id' => 35,
            'Label' => 'Daylight (0)',
        ),
        22 => array(
            'Id' => 36,
            'Label' => 'Daylight (+1)',
        ),
        23 => array(
            'Id' => 37,
            'Label' => 'Daylight (+2)',
        ),
        24 => array(
            'Id' => 38,
            'Label' => 'Daylight (+3)',
        ),
        25 => array(
            'Id' => 48,
            'Label' => 'Shade (-3)',
        ),
        26 => array(
            'Id' => 49,
            'Label' => 'Shade (-2)',
        ),
        27 => array(
            'Id' => 50,
            'Label' => 'Shade (-1)',
        ),
        28 => array(
            'Id' => 51,
            'Label' => 'Shade (0)',
        ),
        29 => array(
            'Id' => 52,
            'Label' => 'Shade (+1)',
        ),
        30 => array(
            'Id' => 53,
            'Label' => 'Shade (+2)',
        ),
        31 => array(
            'Id' => 54,
            'Label' => 'Shade (+3)',
        ),
        32 => array(
            'Id' => 64,
            'Label' => 'Cloudy (-3)',
        ),
        33 => array(
            'Id' => 65,
            'Label' => 'Cloudy (-2)',
        ),
        34 => array(
            'Id' => 66,
            'Label' => 'Cloudy (-1)',
        ),
        35 => array(
            'Id' => 67,
            'Label' => 'Cloudy (0)',
        ),
        36 => array(
            'Id' => 68,
            'Label' => 'Cloudy (+1)',
        ),
        37 => array(
            'Id' => 69,
            'Label' => 'Cloudy (+2)',
        ),
        38 => array(
            'Id' => 70,
            'Label' => 'Cloudy (+3)',
        ),
        39 => array(
            'Id' => 80,
            'Label' => 'Tungsten (-3)',
        ),
        40 => array(
            'Id' => 81,
            'Label' => 'Tungsten (-2)',
        ),
        41 => array(
            'Id' => 82,
            'Label' => 'Tungsten (-1)',
        ),
        42 => array(
            'Id' => 83,
            'Label' => 'Tungsten (0)',
        ),
        43 => array(
            'Id' => 84,
            'Label' => 'Tungsten (+1)',
        ),
        44 => array(
            'Id' => 85,
            'Label' => 'Tungsten (+2)',
        ),
        45 => array(
            'Id' => 86,
            'Label' => 'Tungsten (+3)',
        ),
        46 => array(
            'Id' => 96,
            'Label' => 'Fluorescent (-3)',
        ),
        47 => array(
            'Id' => 97,
            'Label' => 'Fluorescent (-2)',
        ),
        48 => array(
            'Id' => 98,
            'Label' => 'Fluorescent (-1)',
        ),
        49 => array(
            'Id' => 99,
            'Label' => 'Fluorescent (0)',
        ),
        50 => array(
            'Id' => 100,
            'Label' => 'Fluorescent (+1)',
        ),
        51 => array(
            'Id' => 101,
            'Label' => 'Fluorescent (+2)',
        ),
        52 => array(
            'Id' => 102,
            'Label' => 'Fluorescent (+3)',
        ),
        53 => array(
            'Id' => 112,
            'Label' => 'Flash (-3)',
        ),
        54 => array(
            'Id' => 113,
            'Label' => 'Flash (-2)',
        ),
        55 => array(
            'Id' => 114,
            'Label' => 'Flash (-1)',
        ),
        56 => array(
            'Id' => 115,
            'Label' => 'Flash (0)',
        ),
        57 => array(
            'Id' => 116,
            'Label' => 'Flash (+1)',
        ),
        58 => array(
            'Id' => 117,
            'Label' => 'Flash (+2)',
        ),
        59 => array(
            'Id' => 118,
            'Label' => 'Flash (+3)',
        ),
        60 => array(
            'Id' => 163,
            'Label' => 'Custom',
        ),
        61 => array(
            'Id' => 243,
            'Label' => 'Color Temperature/Color Filter',
        ),
        62 => array(
            'Id' => 16,
            'Label' => 'Auto (-3)',
        ),
        63 => array(
            'Id' => 17,
            'Label' => 'Auto (-2)',
        ),
        64 => array(
            'Id' => 18,
            'Label' => 'Auto (-1)',
        ),
        65 => array(
            'Id' => 19,
            'Label' => 'Auto (0)',
        ),
        66 => array(
            'Id' => 20,
            'Label' => 'Auto (+1)',
        ),
        67 => array(
            'Id' => 21,
            'Label' => 'Auto (+2)',
        ),
        68 => array(
            'Id' => 22,
            'Label' => 'Auto (+3)',
        ),
        69 => array(
            'Id' => 32,
            'Label' => 'Daylight (-3)',
        ),
        70 => array(
            'Id' => 33,
            'Label' => 'Daylight (-2)',
        ),
        71 => array(
            'Id' => 34,
            'Label' => 'Daylight (-1)',
        ),
        72 => array(
            'Id' => 35,
            'Label' => 'Daylight (0)',
        ),
        73 => array(
            'Id' => 36,
            'Label' => 'Daylight (+1)',
        ),
        74 => array(
            'Id' => 37,
            'Label' => 'Daylight (+2)',
        ),
        75 => array(
            'Id' => 38,
            'Label' => 'Daylight (+3)',
        ),
        76 => array(
            'Id' => 48,
            'Label' => 'Shade (-3)',
        ),
        77 => array(
            'Id' => 49,
            'Label' => 'Shade (-2)',
        ),
        78 => array(
            'Id' => 50,
            'Label' => 'Shade (-1)',
        ),
        79 => array(
            'Id' => 51,
            'Label' => 'Shade (0)',
        ),
        80 => array(
            'Id' => 52,
            'Label' => 'Shade (+1)',
        ),
        81 => array(
            'Id' => 53,
            'Label' => 'Shade (+2)',
        ),
        82 => array(
            'Id' => 54,
            'Label' => 'Shade (+3)',
        ),
        83 => array(
            'Id' => 64,
            'Label' => 'Cloudy (-3)',
        ),
        84 => array(
            'Id' => 65,
            'Label' => 'Cloudy (-2)',
        ),
        85 => array(
            'Id' => 66,
            'Label' => 'Cloudy (-1)',
        ),
        86 => array(
            'Id' => 67,
            'Label' => 'Cloudy (0)',
        ),
        87 => array(
            'Id' => 68,
            'Label' => 'Cloudy (+1)',
        ),
        88 => array(
            'Id' => 69,
            'Label' => 'Cloudy (+2)',
        ),
        89 => array(
            'Id' => 70,
            'Label' => 'Cloudy (+3)',
        ),
        90 => array(
            'Id' => 80,
            'Label' => 'Tungsten (-3)',
        ),
        91 => array(
            'Id' => 81,
            'Label' => 'Tungsten (-2)',
        ),
        92 => array(
            'Id' => 82,
            'Label' => 'Tungsten (-1)',
        ),
        93 => array(
            'Id' => 83,
            'Label' => 'Tungsten (0)',
        ),
        94 => array(
            'Id' => 84,
            'Label' => 'Tungsten (+1)',
        ),
        95 => array(
            'Id' => 85,
            'Label' => 'Tungsten (+2)',
        ),
        96 => array(
            'Id' => 86,
            'Label' => 'Tungsten (+3)',
        ),
        97 => array(
            'Id' => 96,
            'Label' => 'Fluorescent (-3)',
        ),
        98 => array(
            'Id' => 97,
            'Label' => 'Fluorescent (-2)',
        ),
        99 => array(
            'Id' => 98,
            'Label' => 'Fluorescent (-1)',
        ),
        100 => array(
            'Id' => 99,
            'Label' => 'Fluorescent (0)',
        ),
        101 => array(
            'Id' => 100,
            'Label' => 'Fluorescent (+1)',
        ),
        102 => array(
            'Id' => 101,
            'Label' => 'Fluorescent (+2)',
        ),
        103 => array(
            'Id' => 102,
            'Label' => 'Fluorescent (+3)',
        ),
        104 => array(
            'Id' => 112,
            'Label' => 'Flash (-3)',
        ),
        105 => array(
            'Id' => 113,
            'Label' => 'Flash (-2)',
        ),
        106 => array(
            'Id' => 114,
            'Label' => 'Flash (-1)',
        ),
        107 => array(
            'Id' => 115,
            'Label' => 'Flash (0)',
        ),
        108 => array(
            'Id' => 116,
            'Label' => 'Flash (+1)',
        ),
        109 => array(
            'Id' => 117,
            'Label' => 'Flash (+2)',
        ),
        110 => array(
            'Id' => 118,
            'Label' => 'Flash (+3)',
        ),
        111 => array(
            'Id' => 163,
            'Label' => 'Custom',
        ),
        112 => array(
            'Id' => 243,
            'Label' => 'Color Temperature/Color Filter',
        ),
    );

}
