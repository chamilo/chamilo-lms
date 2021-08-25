<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PanasonicRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBType7 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WBType7';

    protected $FullName = 'mixed';

    protected $GroupName = 'PanasonicRaw';

    protected $g0 = 'PanasonicRaw';

    protected $g1 = 'PanasonicRaw';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'WB Type 7';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
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
            'Label' => 'Tungsten (Incandescent)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Flash',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Fine Weather',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Cloudy',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Shade',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Daylight Fluorescent',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Day White Fluorescent',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Cool White Fluorescent',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'White Fluorescent',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Warm White Fluorescent',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Standard Light A',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Standard Light B',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Standard Light C',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'D55',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'D65',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'D75',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'D50',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'ISO Studio Tungsten',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Other',
        ),
    );

}
