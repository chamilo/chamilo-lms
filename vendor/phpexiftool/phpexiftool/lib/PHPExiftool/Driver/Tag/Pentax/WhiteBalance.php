<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class WhiteBalance extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WhiteBalance';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

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
            'Label' => 'Shade',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Fluorescent',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Tungsten',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Shade',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'Fluorescent',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'Tungsten',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
        12 => array(
            'Id' => 6,
            'Label' => 'Daylight Fluorescent',
        ),
        13 => array(
            'Id' => 7,
            'Label' => 'Day White Fluorescent',
        ),
        14 => array(
            'Id' => 8,
            'Label' => 'White Fluorescent',
        ),
        15 => array(
            'Id' => 9,
            'Label' => 'Flash',
        ),
        16 => array(
            'Id' => 10,
            'Label' => 'Cloudy',
        ),
        17 => array(
            'Id' => 15,
            'Label' => 'Color Temperature Enhancement',
        ),
        18 => array(
            'Id' => 17,
            'Label' => 'Kelvin',
        ),
        19 => array(
            'Id' => 65534,
            'Label' => 'Unknown',
        ),
        20 => array(
            'Id' => 65535,
            'Label' => 'User-Selected',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        22 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        23 => array(
            'Id' => 2,
            'Label' => 'Shade',
        ),
        24 => array(
            'Id' => 3,
            'Label' => 'Tungsten',
        ),
        25 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        26 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
    );

}
