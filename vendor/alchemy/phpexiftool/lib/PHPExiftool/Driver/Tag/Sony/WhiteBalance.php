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
class WhiteBalance extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WhiteBalance';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'White Balance';

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
            'Id' => 12,
            'Label' => 'Color Temperature',
        ),
        6 => array(
            'Id' => 13,
            'Label' => 'Color Filter',
        ),
        7 => array(
            'Id' => 14,
            'Label' => 'Custom',
        ),
        8 => array(
            'Id' => 16,
            'Label' => 'Cloudy',
        ),
        9 => array(
            'Id' => 17,
            'Label' => 'Shade',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Color Temperature/Color Filter',
        ),
        12 => array(
            'Id' => 16,
            'Label' => 'Daylight',
        ),
        13 => array(
            'Id' => 32,
            'Label' => 'Cloudy',
        ),
        14 => array(
            'Id' => 48,
            'Label' => 'Shade',
        ),
        15 => array(
            'Id' => 64,
            'Label' => 'Tungsten',
        ),
        16 => array(
            'Id' => 80,
            'Label' => 'Flash',
        ),
        17 => array(
            'Id' => 96,
            'Label' => 'Fluorescent',
        ),
        18 => array(
            'Id' => 112,
            'Label' => 'Custom',
        ),
        19 => array(
            'Id' => 128,
            'Label' => 'Underwater',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        21 => array(
            'Id' => 4,
            'Label' => 'Custom',
        ),
        22 => array(
            'Id' => 5,
            'Label' => 'Daylight',
        ),
        23 => array(
            'Id' => 6,
            'Label' => 'Cloudy',
        ),
        24 => array(
            'Id' => 7,
            'Label' => 'Cool White Fluorescent',
        ),
        25 => array(
            'Id' => 8,
            'Label' => 'Day White Fluorescent',
        ),
        26 => array(
            'Id' => 9,
            'Label' => 'Daylight Fluorescent',
        ),
        27 => array(
            'Id' => 10,
            'Label' => 'Incandescent2',
        ),
        28 => array(
            'Id' => 11,
            'Label' => 'Warm White Fluorescent',
        ),
        29 => array(
            'Id' => 14,
            'Label' => 'Incandescent',
        ),
        30 => array(
            'Id' => 15,
            'Label' => 'Flash',
        ),
        31 => array(
            'Id' => 17,
            'Label' => 'Underwater 1 (Blue Water)',
        ),
        32 => array(
            'Id' => 18,
            'Label' => 'Underwater 2 (Green Water)',
        ),
        33 => array(
            'Id' => 19,
            'Label' => 'Underwater Auto',
        ),
    );

}
