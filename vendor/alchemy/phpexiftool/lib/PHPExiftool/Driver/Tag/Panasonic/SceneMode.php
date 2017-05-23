<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class SceneMode extends AbstractTag
{

    protected $Id = 32769;

    protected $Name = 'SceneMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Scene Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
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
        48 => array(
            'Id' => 48,
            'Label' => 'Movie',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'HDR',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Peripheral Defocus',
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
        62 => array(
            'Id' => 62,
            'Label' => 'Panorama',
        ),
        63 => array(
            'Id' => 63,
            'Label' => 'Glass Through',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'HDR',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Digital Filter',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Clear Portrait',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'Silky Skin',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'Backlit Softness',
        ),
        70 => array(
            'Id' => 70,
            'Label' => 'Clear in Backlight',
        ),
        71 => array(
            'Id' => 71,
            'Label' => 'Relaxing Tone',
        ),
        72 => array(
            'Id' => 72,
            'Label' => 'Sweet Child\'s Face',
        ),
        73 => array(
            'Id' => 73,
            'Label' => 'Distinct Scenery',
        ),
        74 => array(
            'Id' => 74,
            'Label' => 'Bright Blue Sky',
        ),
        75 => array(
            'Id' => 75,
            'Label' => 'Romantic Sunset Glow',
        ),
        76 => array(
            'Id' => 76,
            'Label' => 'Vivid Sunset Glow',
        ),
        77 => array(
            'Id' => 77,
            'Label' => 'Glistening Water',
        ),
        78 => array(
            'Id' => 78,
            'Label' => 'Clear Nightscape',
        ),
        79 => array(
            'Id' => 79,
            'Label' => 'Cool Night Sky',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Warm Glowing Nightscape',
        ),
        81 => array(
            'Id' => 81,
            'Label' => 'Artistic Nightscape',
        ),
        82 => array(
            'Id' => 82,
            'Label' => 'Glittering Illuminations',
        ),
        83 => array(
            'Id' => 83,
            'Label' => 'Clear Night Portrait',
        ),
        84 => array(
            'Id' => 84,
            'Label' => 'Soft Image of a Flower',
        ),
        85 => array(
            'Id' => 85,
            'Label' => 'Appetizing Food',
        ),
        86 => array(
            'Id' => 86,
            'Label' => 'Cute Desert',
        ),
        87 => array(
            'Id' => 87,
            'Label' => 'Freeze Animal Motion',
        ),
        88 => array(
            'Id' => 88,
            'Label' => 'Clear Sports Shot',
        ),
        89 => array(
            'Id' => 89,
            'Label' => 'Monochrome',
        ),
        90 => array(
            'Id' => 90,
            'Label' => 'Creative Control',
        ),
    );

}
