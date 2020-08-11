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
class EasyMode extends AbstractTag
{

    protected $Id = 11;

    protected $Name = 'EasyMode';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Easy Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Full auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Landscape',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Fast shutter',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Slow shutter',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Night',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Gray Scale',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Sepia',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Sports',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Macro',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Black & White',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Pan focus',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Vivid',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Neutral',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Flash Off',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Long Shutter',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Super Macro',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Foliage',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Indoor',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Fireworks',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Underwater',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Snow',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Kids & Pets',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Night Snapshot',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Digital Macro',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'My Colors',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Movie Snap',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Super Macro 2',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Color Accent',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Color Swap',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Aquarium',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'ISO 3200',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'ISO 6400',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Creative Light Effect',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Easy',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Quick Shot',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Creative Auto',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Zoom Blur',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Low Light',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Nostalgic',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'Super Vivid',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Poster Effect',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Face Self-timer',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Smile',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'Wink Self-timer',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'Fisheye Effect',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Miniature Effect',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'High-speed Burst',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Best Image Selection',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'High Dynamic Range',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Handheld Night Scene',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Movie Digest',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'Live View Control',
        ),
        55 => array(
            'Id' => 55,
            'Label' => 'Discreet',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'Blur Reduction',
        ),
        57 => array(
            'Id' => 57,
            'Label' => 'Monochrome',
        ),
        58 => array(
            'Id' => 58,
            'Label' => 'Toy Camera Effect',
        ),
        59 => array(
            'Id' => 59,
            'Label' => 'Scene Intelligent Auto',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'High-speed Burst HQ',
        ),
        61 => array(
            'Id' => 61,
            'Label' => 'Smooth Skin',
        ),
        62 => array(
            'Id' => 62,
            'Label' => 'Soft Focus',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'Spotlight',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'Night 2',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'Night+',
        ),
        260 => array(
            'Id' => 260,
            'Label' => 'Super Night',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'Sunset',
        ),
        263 => array(
            'Id' => 263,
            'Label' => 'Night Scene',
        ),
        264 => array(
            'Id' => 264,
            'Label' => 'Surface',
        ),
        265 => array(
            'Id' => 265,
            'Label' => 'Low Light 2',
        ),
    );

}
