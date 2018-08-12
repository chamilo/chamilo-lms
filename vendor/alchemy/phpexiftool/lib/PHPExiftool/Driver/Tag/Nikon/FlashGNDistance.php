<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashGNDistance extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashGNDistance';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash GN Distance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        1 => array(
            'Id' => 1,
            'Label' => '0.1 m',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '0.2 m',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '0.3 m',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '0.4 m',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '0.5 m',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '0.6 m',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '0.7 m',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '0.8 m',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '0.9 m',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '1.0 m',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '1.1 m',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '1.3 m',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '1.4 m',
        ),
        14 => array(
            'Id' => 14,
            'Label' => '1.6 m',
        ),
        15 => array(
            'Id' => 15,
            'Label' => '1.8 m',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '2.0 m',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '2.2 m',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '2.5 m',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '2.8 m',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '3.2 m',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '3.6 m',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '4.0 m',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '4.5 m',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '5.0 m',
        ),
        25 => array(
            'Id' => 25,
            'Label' => '5.6 m',
        ),
        26 => array(
            'Id' => 26,
            'Label' => '6.3 m',
        ),
        27 => array(
            'Id' => 27,
            'Label' => '7.1 m',
        ),
        28 => array(
            'Id' => 28,
            'Label' => '8.0 m',
        ),
        29 => array(
            'Id' => 29,
            'Label' => '9.0 m',
        ),
        30 => array(
            'Id' => 30,
            'Label' => '10.0 m',
        ),
        31 => array(
            'Id' => 31,
            'Label' => '11.0 m',
        ),
        32 => array(
            'Id' => 32,
            'Label' => '13.0 m',
        ),
        33 => array(
            'Id' => 33,
            'Label' => '14.0 m',
        ),
        34 => array(
            'Id' => 34,
            'Label' => '16.0 m',
        ),
        35 => array(
            'Id' => 35,
            'Label' => '18.0 m',
        ),
        36 => array(
            'Id' => 36,
            'Label' => '20.0 m',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'n/a',
        ),
    );

}
