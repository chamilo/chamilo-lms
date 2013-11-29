<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'White Balance Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Auto (-3)',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Auto (-2)',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Auto (-1)',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Auto (0)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Auto (+1)',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Auto (+2)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Auto (+3)',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Daylight (-3)',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Daylight (-2)',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Daylight (-1)',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Daylight (0)',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Daylight (+1)',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Daylight (+2)',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Daylight (+3)',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Shade (-3)',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Shade (-2)',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Shade (-1)',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Shade (0)',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Shade (+1)',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Shade (+2)',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'Shade (+3)',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Cloudy (-3)',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Cloudy (-2)',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Cloudy (-1)',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Cloudy (0)',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'Cloudy (+1)',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'Cloudy (+2)',
        ),
        70 => array(
            'Id' => 70,
            'Label' => 'Cloudy (+3)',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Tungsten (-3)',
        ),
        81 => array(
            'Id' => 81,
            'Label' => 'Tungsten (-2)',
        ),
        82 => array(
            'Id' => 82,
            'Label' => 'Tungsten (-1)',
        ),
        83 => array(
            'Id' => 83,
            'Label' => 'Tungsten (0)',
        ),
        84 => array(
            'Id' => 84,
            'Label' => 'Tungsten (+1)',
        ),
        85 => array(
            'Id' => 85,
            'Label' => 'Tungsten (+2)',
        ),
        86 => array(
            'Id' => 86,
            'Label' => 'Tungsten (+3)',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Fluorescent (-3)',
        ),
        97 => array(
            'Id' => 97,
            'Label' => 'Fluorescent (-2)',
        ),
        98 => array(
            'Id' => 98,
            'Label' => 'Fluorescent (-1)',
        ),
        99 => array(
            'Id' => 99,
            'Label' => 'Fluorescent (0)',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'Fluorescent (+1)',
        ),
        101 => array(
            'Id' => 101,
            'Label' => 'Fluorescent (+2)',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'Fluorescent (+3)',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'Flash (-3)',
        ),
        113 => array(
            'Id' => 113,
            'Label' => 'Flash (-2)',
        ),
        114 => array(
            'Id' => 114,
            'Label' => 'Flash (-1)',
        ),
        115 => array(
            'Id' => 115,
            'Label' => 'Flash (0)',
        ),
        116 => array(
            'Id' => 116,
            'Label' => 'Flash (+1)',
        ),
        117 => array(
            'Id' => 117,
            'Label' => 'Flash (+2)',
        ),
        118 => array(
            'Id' => 118,
            'Label' => 'Flash (+3)',
        ),
        163 => array(
            'Id' => 163,
            'Label' => 'Custom',
        ),
        243 => array(
            'Id' => 243,
            'Label' => 'Color Temperature/Color Filter',
        ),
    );

}
