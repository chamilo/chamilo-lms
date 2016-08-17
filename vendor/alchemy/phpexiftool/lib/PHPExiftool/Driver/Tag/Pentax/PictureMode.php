<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PictureMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PictureMode';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Picture Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        '0.1' => array(
            'Id' => '0.1',
            'Label' => 'Av',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Shutter Speed Priority',
        ),
        '1.1' => array(
            'Id' => '1.1',
            'Label' => 'M',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        '2.1' => array(
            'Id' => '2.1',
            'Label' => 'Tv',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        '3.1' => array(
            'Id' => '3.1',
            'Label' => 'USER',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Portrait',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Landscape',
        ),
        6 => array(
            'Id' => 8,
            'Label' => 'Sport',
        ),
        7 => array(
            'Id' => 9,
            'Label' => 'Night Scene',
        ),
        8 => array(
            'Id' => 11,
            'Label' => 'Soft',
        ),
        9 => array(
            'Id' => 12,
            'Label' => 'Surf & Snow',
        ),
        10 => array(
            'Id' => 13,
            'Label' => 'Candlelight',
        ),
        11 => array(
            'Id' => 14,
            'Label' => 'Autumn',
        ),
        12 => array(
            'Id' => 15,
            'Label' => 'Macro',
        ),
        13 => array(
            'Id' => 17,
            'Label' => 'Fireworks',
        ),
        14 => array(
            'Id' => 18,
            'Label' => 'Text',
        ),
        15 => array(
            'Id' => 19,
            'Label' => 'Panorama',
        ),
        16 => array(
            'Id' => 20,
            'Label' => '3-D',
        ),
        17 => array(
            'Id' => 21,
            'Label' => 'Black & White',
        ),
        18 => array(
            'Id' => 22,
            'Label' => 'Sepia',
        ),
        19 => array(
            'Id' => 23,
            'Label' => 'Red',
        ),
        20 => array(
            'Id' => 24,
            'Label' => 'Pink',
        ),
        21 => array(
            'Id' => 25,
            'Label' => 'Purple',
        ),
        22 => array(
            'Id' => 26,
            'Label' => 'Blue',
        ),
        23 => array(
            'Id' => 27,
            'Label' => 'Green',
        ),
        24 => array(
            'Id' => 28,
            'Label' => 'Yellow',
        ),
        25 => array(
            'Id' => 30,
            'Label' => 'Self Portrait',
        ),
        26 => array(
            'Id' => 31,
            'Label' => 'Illustrations',
        ),
        27 => array(
            'Id' => 33,
            'Label' => 'Digital Filter',
        ),
        28 => array(
            'Id' => 35,
            'Label' => 'Night Scene Portrait',
        ),
        29 => array(
            'Id' => 37,
            'Label' => 'Museum',
        ),
        30 => array(
            'Id' => 38,
            'Label' => 'Food',
        ),
        31 => array(
            'Id' => 39,
            'Label' => 'Underwater',
        ),
        32 => array(
            'Id' => 40,
            'Label' => 'Green Mode',
        ),
        33 => array(
            'Id' => 49,
            'Label' => 'Light Pet',
        ),
        34 => array(
            'Id' => 50,
            'Label' => 'Dark Pet',
        ),
        35 => array(
            'Id' => 51,
            'Label' => 'Medium Pet',
        ),
        36 => array(
            'Id' => 53,
            'Label' => 'Underwater',
        ),
        37 => array(
            'Id' => 54,
            'Label' => 'Candlelight',
        ),
        38 => array(
            'Id' => 55,
            'Label' => 'Natural Skin Tone',
        ),
        39 => array(
            'Id' => 56,
            'Label' => 'Synchro Sound Record',
        ),
        40 => array(
            'Id' => 58,
            'Label' => 'Frame Composite',
        ),
        41 => array(
            'Id' => 59,
            'Label' => 'Report',
        ),
        42 => array(
            'Id' => 60,
            'Label' => 'Kids',
        ),
        43 => array(
            'Id' => 61,
            'Label' => 'Blur Reduction',
        ),
        44 => array(
            'Id' => 63,
            'Label' => 'Panorama 2',
        ),
        45 => array(
            'Id' => 65,
            'Label' => 'Half-length Portrait',
        ),
        46 => array(
            'Id' => 66,
            'Label' => 'Portrait 2',
        ),
        47 => array(
            'Id' => 74,
            'Label' => 'Digital Microscope',
        ),
        48 => array(
            'Id' => 75,
            'Label' => 'Blue Sky',
        ),
        49 => array(
            'Id' => 80,
            'Label' => 'Miniature',
        ),
        50 => array(
            'Id' => 81,
            'Label' => 'HDR',
        ),
        51 => array(
            'Id' => 83,
            'Label' => 'Fisheye',
        ),
        52 => array(
            'Id' => 85,
            'Label' => 'Digital Filter 4',
        ),
        53 => array(
            'Id' => 221,
            'Label' => 'P',
        ),
        54 => array(
            'Id' => 255,
            'Label' => 'PICT',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'Program',
        ),
        '0 1' => array(
            'Id' => '0 1',
            'Label' => 'Hi-speed Program',
        ),
        '0 2' => array(
            'Id' => '0 2',
            'Label' => 'DOF Program',
        ),
        '0 3' => array(
            'Id' => '0 3',
            'Label' => 'MTF Program',
        ),
        '0 4' => array(
            'Id' => '0 4',
            'Label' => 'Standard',
        ),
        '0 5' => array(
            'Id' => '0 5',
            'Label' => 'Portrait',
        ),
        '0 6' => array(
            'Id' => '0 6',
            'Label' => 'Landscape',
        ),
        '0 7' => array(
            'Id' => '0 7',
            'Label' => 'Macro',
        ),
        '0 8' => array(
            'Id' => '0 8',
            'Label' => 'Sport',
        ),
        '0 9' => array(
            'Id' => '0 9',
            'Label' => 'Night Scene Portrait',
        ),
        '0 10' => array(
            'Id' => '0 10',
            'Label' => 'No Flash',
        ),
        '0 11' => array(
            'Id' => '0 11',
            'Label' => 'Night Scene',
        ),
        '0 12' => array(
            'Id' => '0 12',
            'Label' => 'Surf & Snow',
        ),
        '0 13' => array(
            'Id' => '0 13',
            'Label' => 'Text',
        ),
        '0 14' => array(
            'Id' => '0 14',
            'Label' => 'Sunset',
        ),
        '0 15' => array(
            'Id' => '0 15',
            'Label' => 'Kids',
        ),
        '0 16' => array(
            'Id' => '0 16',
            'Label' => 'Pet',
        ),
        '0 17' => array(
            'Id' => '0 17',
            'Label' => 'Candlelight',
        ),
        '0 18' => array(
            'Id' => '0 18',
            'Label' => 'Museum',
        ),
        '0 19' => array(
            'Id' => '0 19',
            'Label' => 'Food',
        ),
        '0 20' => array(
            'Id' => '0 20',
            'Label' => 'Stage Lighting',
        ),
        '0 21' => array(
            'Id' => '0 21',
            'Label' => 'Night Snap',
        ),
        '0 23' => array(
            'Id' => '0 23',
            'Label' => 'Blue Sky',
        ),
        '0 24' => array(
            'Id' => '0 24',
            'Label' => 'Sunset',
        ),
        '0 26' => array(
            'Id' => '0 26',
            'Label' => 'Night Scene HDR',
        ),
        '0 27' => array(
            'Id' => '0 27',
            'Label' => 'HDR',
        ),
        '0 28' => array(
            'Id' => '0 28',
            'Label' => 'Quick Macro',
        ),
        '0 29' => array(
            'Id' => '0 29',
            'Label' => 'Forest',
        ),
        '0 30' => array(
            'Id' => '0 30',
            'Label' => 'Backlight Silhouette',
        ),
        '1 4' => array(
            'Id' => '1 4',
            'Label' => 'Auto PICT (Standard)',
        ),
        '1 5' => array(
            'Id' => '1 5',
            'Label' => 'Auto PICT (Portrait)',
        ),
        '1 6' => array(
            'Id' => '1 6',
            'Label' => 'Auto PICT (Landscape)',
        ),
        '1 7' => array(
            'Id' => '1 7',
            'Label' => 'Auto PICT (Macro)',
        ),
        '1 8' => array(
            'Id' => '1 8',
            'Label' => 'Auto PICT (Sport)',
        ),
        '2 0' => array(
            'Id' => '2 0',
            'Label' => 'Program (HyP)',
        ),
        '2 1' => array(
            'Id' => '2 1',
            'Label' => 'Hi-speed Program (HyP)',
        ),
        '2 2' => array(
            'Id' => '2 2',
            'Label' => 'DOF Program (HyP)',
        ),
        '2 3' => array(
            'Id' => '2 3',
            'Label' => 'MTF Program (HyP)',
        ),
        '2 22' => array(
            'Id' => '2 22',
            'Label' => 'Shallow DOF (HyP)',
        ),
        '3 0' => array(
            'Id' => '3 0',
            'Label' => 'Green Mode',
        ),
        '4 0' => array(
            'Id' => '4 0',
            'Label' => 'Shutter Speed Priority',
        ),
        '5 0' => array(
            'Id' => '5 0',
            'Label' => 'Aperture Priority',
        ),
        '6 0' => array(
            'Id' => '6 0',
            'Label' => 'Program Tv Shift',
        ),
        '7 0' => array(
            'Id' => '7 0',
            'Label' => 'Program Av Shift',
        ),
        '8 0' => array(
            'Id' => '8 0',
            'Label' => 'Manual',
        ),
        '9 0' => array(
            'Id' => '9 0',
            'Label' => 'Bulb',
        ),
        '10 0' => array(
            'Id' => '10 0',
            'Label' => 'Aperture Priority, Off-Auto-Aperture',
        ),
        '11 0' => array(
            'Id' => '11 0',
            'Label' => 'Manual, Off-Auto-Aperture',
        ),
        '12 0' => array(
            'Id' => '12 0',
            'Label' => 'Bulb, Off-Auto-Aperture',
        ),
        '13 0' => array(
            'Id' => '13 0',
            'Label' => 'Shutter & Aperture Priority AE',
        ),
        '15 0' => array(
            'Id' => '15 0',
            'Label' => 'Sensitivity Priority AE',
        ),
        '16 0' => array(
            'Id' => '16 0',
            'Label' => 'Flash X-Sync Speed AE',
        ),
        '18 0' => array(
            'Id' => '18 0',
            'Label' => 'Auto Program (Normal)',
        ),
        '18 1' => array(
            'Id' => '18 1',
            'Label' => 'Auto Program (Hi-speed)',
        ),
        '18 2' => array(
            'Id' => '18 2',
            'Label' => 'Auto Program (DOF)',
        ),
        '18 3' => array(
            'Id' => '18 3',
            'Label' => 'Auto Program (MTF)',
        ),
        '18 22' => array(
            'Id' => '18 22',
            'Label' => 'Auto Program (Shallow DOF)',
        ),
        '20 22' => array(
            'Id' => '20 22',
            'Label' => 'Blur Control',
        ),
        '254 0' => array(
            'Id' => '254 0',
            'Label' => 'Video',
        ),
        '255 0' => array(
            'Id' => '255 0',
            'Label' => 'Video (Auto Aperture)',
        ),
        '255 4' => array(
            'Id' => '255 4',
            'Label' => 'Video (4)',
        ),
    );

    protected $MaxLength = 'mixed';

}
