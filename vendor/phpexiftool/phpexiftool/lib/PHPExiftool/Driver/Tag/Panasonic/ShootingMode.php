<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ShootingMode extends AbstractTag
{

    protected $Id = 31;

    protected $Name = 'ShootingMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Shooting Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Scenery',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Sports',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Night Portrait',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Program',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Aperture Priority',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Shutter Priority',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Macro',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Spot',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Manual',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Movie Preview',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Panning',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Simple',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Color Effects',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Self Portrait',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Economy',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Fireworks',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Party',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Snow',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Night Scenery',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Food',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Baby',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Soft Skin',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Candlelight',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Starry Night',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'High Sensitivity',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Panorama Assist',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Underwater',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Beach',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Aerial Photo',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Sunset',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Pet',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Intelligent ISO',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Clipboard',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'High Speed Continuous Shooting',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Intelligent Auto',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Multi-aspect',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Transform',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'Flash Burst',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Pin Hole',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Film Grain',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'My Color',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'Photo Frame',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'HDR',
        ),
        55 => array(
            'Id' => 55,
            'Label' => 'Handheld Night Shot',
        ),
        57 => array(
            'Id' => 57,
            'Label' => '3D',
        ),
        59 => array(
            'Id' => 59,
            'Label' => 'Creative Control',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Digital Filter',
        ),
    );

}
