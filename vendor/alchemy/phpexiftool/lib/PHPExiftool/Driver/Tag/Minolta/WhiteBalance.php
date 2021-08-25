<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

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

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Shade',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Tungsten',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Fluorescent',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Flash',
        ),
        7 => array(
            'Id' => 256,
            'Label' => 'Kelvin',
        ),
        8 => array(
            'Id' => 512,
            'Label' => 'Manual',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Shade',
        ),
        12 => array(
            'Id' => 3,
            'Label' => 'Cloudy',
        ),
        13 => array(
            'Id' => 4,
            'Label' => 'Tungsten',
        ),
        14 => array(
            'Id' => 5,
            'Label' => 'Fluorescent',
        ),
        15 => array(
            'Id' => 256,
            'Label' => 'Kelvin',
        ),
        16 => array(
            'Id' => 512,
            'Label' => 'Manual',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        18 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        19 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Shade',
        ),
        21 => array(
            'Id' => 4,
            'Label' => 'Tungsten',
        ),
        22 => array(
            'Id' => 5,
            'Label' => 'Fluorescent',
        ),
        23 => array(
            'Id' => 6,
            'Label' => 'Flash',
        ),
        24 => array(
            'Id' => 256,
            'Label' => 'Kelvin',
        ),
        25 => array(
            'Id' => 512,
            'Label' => 'Manual',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'Color Temperature/Color Filter',
        ),
        28 => array(
            'Id' => 16,
            'Label' => 'Daylight',
        ),
        29 => array(
            'Id' => 32,
            'Label' => 'Cloudy',
        ),
        30 => array(
            'Id' => 48,
            'Label' => 'Shade',
        ),
        31 => array(
            'Id' => 64,
            'Label' => 'Tungsten',
        ),
        32 => array(
            'Id' => 80,
            'Label' => 'Flash',
        ),
        33 => array(
            'Id' => 96,
            'Label' => 'Fluorescent',
        ),
        34 => array(
            'Id' => 112,
            'Label' => 'Custom',
        ),
    );

}
