<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

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

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

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
            'Label' => 'Tungsten',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'Detail',
        ),
        7 => array(
            'Id' => 9,
            'Label' => 'Multi-pattern Auto',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Multi-P Auto',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Daylight',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'Cloudy',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'Incandescent 1',
        ),
        13 => array(
            'Id' => 5,
            'Label' => 'Incandescent 2',
        ),
        14 => array(
            'Id' => 6,
            'Label' => 'Daylight Fluorescent',
        ),
        15 => array(
            'Id' => 7,
            'Label' => 'Neutral White Fluorescent',
        ),
        16 => array(
            'Id' => 8,
            'Label' => 'Cool White Fluorescent',
        ),
        17 => array(
            'Id' => 9,
            'Label' => 'Warm White Fluorescent',
        ),
        18 => array(
            'Id' => 10,
            'Label' => 'Manual',
        ),
        19 => array(
            'Id' => 11,
            'Label' => 'Kelvin',
        ),
        20 => array(
            'Id' => 12,
            'Label' => 'Shade',
        ),
    );

    protected $Index = 'mixed';

}
