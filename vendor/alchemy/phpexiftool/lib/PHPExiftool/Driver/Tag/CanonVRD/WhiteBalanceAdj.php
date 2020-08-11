<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalanceAdj extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WhiteBalanceAdj';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'White Balance Adj';

    protected $Values = array(
        0 => array(
            'Id' => '-1',
            'Label' => 'Manual (Click)',
        ),
        1 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        3 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        4 => array(
            'Id' => 3,
            'Label' => 'Tungsten',
        ),
        5 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        6 => array(
            'Id' => 5,
            'Label' => 'Flash',
        ),
        7 => array(
            'Id' => 8,
            'Label' => 'Shade',
        ),
        8 => array(
            'Id' => 9,
            'Label' => 'Kelvin',
        ),
        9 => array(
            'Id' => 255,
            'Label' => 'Shot Settings',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Tungsten',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        15 => array(
            'Id' => 5,
            'Label' => 'Flash',
        ),
        16 => array(
            'Id' => 8,
            'Label' => 'Shade',
        ),
        17 => array(
            'Id' => 9,
            'Label' => 'Kelvin',
        ),
        18 => array(
            'Id' => 30,
            'Label' => 'Manual (Click)',
        ),
        19 => array(
            'Id' => 31,
            'Label' => 'Shot Settings',
        ),
    );

}
