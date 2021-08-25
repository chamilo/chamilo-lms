<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SceneMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SceneMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Scene Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 6,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 7,
            'Label' => 'Sport',
        ),
        3 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        4 => array(
            'Id' => 9,
            'Label' => 'Landscape+Portrait',
        ),
        5 => array(
            'Id' => 10,
            'Label' => 'Landscape',
        ),
        6 => array(
            'Id' => 11,
            'Label' => 'Night Scene',
        ),
        7 => array(
            'Id' => 12,
            'Label' => 'Self Portrait',
        ),
        8 => array(
            'Id' => 13,
            'Label' => 'Panorama',
        ),
        9 => array(
            'Id' => 14,
            'Label' => '2 in 1',
        ),
        10 => array(
            'Id' => 15,
            'Label' => 'Movie',
        ),
        11 => array(
            'Id' => 16,
            'Label' => 'Landscape+Portrait',
        ),
        12 => array(
            'Id' => 17,
            'Label' => 'Night+Portrait',
        ),
        13 => array(
            'Id' => 18,
            'Label' => 'Indoor',
        ),
        14 => array(
            'Id' => 19,
            'Label' => 'Fireworks',
        ),
        15 => array(
            'Id' => 20,
            'Label' => 'Sunset',
        ),
        16 => array(
            'Id' => 21,
            'Label' => 'Beauty Skin',
        ),
        17 => array(
            'Id' => 22,
            'Label' => 'Macro',
        ),
        18 => array(
            'Id' => 23,
            'Label' => 'Super Macro',
        ),
        19 => array(
            'Id' => 24,
            'Label' => 'Food',
        ),
        20 => array(
            'Id' => 25,
            'Label' => 'Documents',
        ),
        21 => array(
            'Id' => 26,
            'Label' => 'Museum',
        ),
        22 => array(
            'Id' => 27,
            'Label' => 'Shoot & Select',
        ),
        23 => array(
            'Id' => 28,
            'Label' => 'Beach & Snow',
        ),
        24 => array(
            'Id' => 29,
            'Label' => 'Self Protrait+Timer',
        ),
        25 => array(
            'Id' => 30,
            'Label' => 'Candle',
        ),
        26 => array(
            'Id' => 31,
            'Label' => 'Available Light',
        ),
        27 => array(
            'Id' => 32,
            'Label' => 'Behind Glass',
        ),
        28 => array(
            'Id' => 33,
            'Label' => 'My Mode',
        ),
        29 => array(
            'Id' => 34,
            'Label' => 'Pet',
        ),
        30 => array(
            'Id' => 35,
            'Label' => 'Underwater Wide1',
        ),
        31 => array(
            'Id' => 36,
            'Label' => 'Underwater Macro',
        ),
        32 => array(
            'Id' => 37,
            'Label' => 'Shoot & Select1',
        ),
        33 => array(
            'Id' => 38,
            'Label' => 'Shoot & Select2',
        ),
        34 => array(
            'Id' => 39,
            'Label' => 'High Key',
        ),
        35 => array(
            'Id' => 40,
            'Label' => 'Digital Image Stabilization',
        ),
        36 => array(
            'Id' => 41,
            'Label' => 'Auction',
        ),
        37 => array(
            'Id' => 42,
            'Label' => 'Beach',
        ),
        38 => array(
            'Id' => 43,
            'Label' => 'Snow',
        ),
        39 => array(
            'Id' => 44,
            'Label' => 'Underwater Wide2',
        ),
        40 => array(
            'Id' => 45,
            'Label' => 'Low Key',
        ),
        41 => array(
            'Id' => 46,
            'Label' => 'Children',
        ),
        42 => array(
            'Id' => 47,
            'Label' => 'Vivid',
        ),
        43 => array(
            'Id' => 48,
            'Label' => 'Nature Macro',
        ),
        44 => array(
            'Id' => 49,
            'Label' => 'Underwater Snapshot',
        ),
        45 => array(
            'Id' => 50,
            'Label' => 'Shooting Guide',
        ),
        46 => array(
            'Id' => 54,
            'Label' => 'Face Portrait',
        ),
        47 => array(
            'Id' => 57,
            'Label' => 'Bulb',
        ),
        48 => array(
            'Id' => 59,
            'Label' => 'Smile Shot',
        ),
        49 => array(
            'Id' => 60,
            'Label' => 'Quick Shutter',
        ),
        50 => array(
            'Id' => 63,
            'Label' => 'Slow Shutter',
        ),
        51 => array(
            'Id' => 64,
            'Label' => 'Bird Watching',
        ),
        52 => array(
            'Id' => 65,
            'Label' => 'Multiple Exposure',
        ),
        53 => array(
            'Id' => 66,
            'Label' => 'e-Portrait',
        ),
        54 => array(
            'Id' => 67,
            'Label' => 'Soft Background Shot',
        ),
        55 => array(
            'Id' => 142,
            'Label' => 'Hand-held Starlight',
        ),
        56 => array(
            'Id' => 154,
            'Label' => 'HDR',
        ),
        57 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        58 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        59 => array(
            'Id' => 2,
            'Label' => 'Auto',
        ),
        60 => array(
            'Id' => 3,
            'Label' => 'Intelligent Auto',
        ),
        61 => array(
            'Id' => 4,
            'Label' => 'Portrait',
        ),
        62 => array(
            'Id' => 5,
            'Label' => 'Landscape+Portrait',
        ),
        63 => array(
            'Id' => 6,
            'Label' => 'Landscape',
        ),
        64 => array(
            'Id' => 7,
            'Label' => 'Night Scene',
        ),
        65 => array(
            'Id' => 8,
            'Label' => 'Night+Portrait',
        ),
        66 => array(
            'Id' => 9,
            'Label' => 'Sport',
        ),
        67 => array(
            'Id' => 10,
            'Label' => 'Self Portrait',
        ),
        68 => array(
            'Id' => 11,
            'Label' => 'Indoor',
        ),
        69 => array(
            'Id' => 12,
            'Label' => 'Beach & Snow',
        ),
        70 => array(
            'Id' => 13,
            'Label' => 'Beach',
        ),
        71 => array(
            'Id' => 14,
            'Label' => 'Snow',
        ),
        72 => array(
            'Id' => 15,
            'Label' => 'Self Portrait+Self Timer',
        ),
        73 => array(
            'Id' => 16,
            'Label' => 'Sunset',
        ),
        74 => array(
            'Id' => 17,
            'Label' => 'Cuisine',
        ),
        75 => array(
            'Id' => 18,
            'Label' => 'Documents',
        ),
        76 => array(
            'Id' => 19,
            'Label' => 'Candle',
        ),
        77 => array(
            'Id' => 20,
            'Label' => 'Fireworks',
        ),
        78 => array(
            'Id' => 21,
            'Label' => 'Available Light',
        ),
        79 => array(
            'Id' => 22,
            'Label' => 'Vivid',
        ),
        80 => array(
            'Id' => 23,
            'Label' => 'Underwater Wide1',
        ),
        81 => array(
            'Id' => 24,
            'Label' => 'Underwater Macro',
        ),
        82 => array(
            'Id' => 25,
            'Label' => 'Museum',
        ),
        83 => array(
            'Id' => 26,
            'Label' => 'Behind Glass',
        ),
        84 => array(
            'Id' => 27,
            'Label' => 'Auction',
        ),
        85 => array(
            'Id' => 28,
            'Label' => 'Shoot & Select1',
        ),
        86 => array(
            'Id' => 29,
            'Label' => 'Shoot & Select2',
        ),
        87 => array(
            'Id' => 30,
            'Label' => 'Underwater Wide2',
        ),
        88 => array(
            'Id' => 31,
            'Label' => 'Digital Image Stabilization',
        ),
        89 => array(
            'Id' => 32,
            'Label' => 'Face Portrait',
        ),
        90 => array(
            'Id' => 33,
            'Label' => 'Pet',
        ),
        91 => array(
            'Id' => 34,
            'Label' => 'Smile Shot',
        ),
        92 => array(
            'Id' => 35,
            'Label' => 'Quick Shutter',
        ),
        93 => array(
            'Id' => 43,
            'Label' => 'Hand-held Starlight',
        ),
        94 => array(
            'Id' => 100,
            'Label' => 'Panorama',
        ),
        95 => array(
            'Id' => 101,
            'Label' => 'Magic Filter',
        ),
        96 => array(
            'Id' => 103,
            'Label' => 'HDR',
        ),
    );

}
