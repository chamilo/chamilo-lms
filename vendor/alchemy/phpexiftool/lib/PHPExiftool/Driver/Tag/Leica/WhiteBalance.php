<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

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

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto or Manual',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Fluorescent',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Tungsten',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Flash',
        ),
        5 => array(
            'Id' => 10,
            'Label' => 'Cloudy',
        ),
        6 => array(
            'Id' => 11,
            'Label' => 'Shade',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Tungsten',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Fluorescent',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'Daylight Fluorescent',
        ),
        11 => array(
            'Id' => 4,
            'Label' => 'Daylight',
        ),
        12 => array(
            'Id' => 5,
            'Label' => 'Flash',
        ),
        13 => array(
            'Id' => 6,
            'Label' => 'Cloudy',
        ),
        14 => array(
            'Id' => 7,
            'Label' => 'Shade',
        ),
        15 => array(
            'Id' => 8,
            'Label' => 'Manual',
        ),
        16 => array(
            'Id' => 9,
            'Label' => 'Kelvin',
        ),
    );

}
