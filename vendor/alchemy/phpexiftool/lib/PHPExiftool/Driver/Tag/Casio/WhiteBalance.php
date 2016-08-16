<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

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

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Tungsten',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Daylight',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Shade',
        ),
        5 => array(
            'Id' => 129,
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
            'Label' => 'Tungsten',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'Shade',
        ),
        16 => array(
            'Id' => 4,
            'Label' => 'Flash?',
        ),
        17 => array(
            'Id' => 6,
            'Label' => 'Fluorescent',
        ),
        18 => array(
            'Id' => 9,
            'Label' => 'Tungsten?',
        ),
        19 => array(
            'Id' => 10,
            'Label' => 'Tungsten',
        ),
        20 => array(
            'Id' => 12,
            'Label' => 'Flash',
        ),
    );

}
