<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ModifiedWhiteBalance extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ModifiedWhiteBalance';

    protected $FullName = 'Canon::ModifiedInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Modified White Balance';

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
            'Label' => 'Flash',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Custom',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Black & White',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Shade',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Manual Temperature (Kelvin)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'PC Set1',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'PC Set2',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'PC Set3',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Daylight Fluorescent',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Custom 1',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Custom 2',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Underwater',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Custom 3',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Custom 4',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'PC Set4',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'PC Set5',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Auto (ambience priority)',
        ),
    );

}
