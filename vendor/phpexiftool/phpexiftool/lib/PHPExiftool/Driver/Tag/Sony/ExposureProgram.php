<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExposureProgram extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureProgram';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Exposure Program';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        5 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        6 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        7 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        8 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        9 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        10 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        11 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        12 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        13 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Program AE',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Aperture-priority AE',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'Shutter speed priority AE',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        18 => array(
            'Id' => 5,
            'Label' => 'Cont. Priority AE',
        ),
        19 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        20 => array(
            'Id' => 17,
            'Label' => 'Auto (no flash)',
        ),
        21 => array(
            'Id' => 18,
            'Label' => 'Auto+',
        ),
        22 => array(
            'Id' => 49,
            'Label' => 'Portrait',
        ),
        23 => array(
            'Id' => 50,
            'Label' => 'Landscape',
        ),
        24 => array(
            'Id' => 51,
            'Label' => 'Macro',
        ),
        25 => array(
            'Id' => 52,
            'Label' => 'Sports',
        ),
        26 => array(
            'Id' => 53,
            'Label' => 'Sunset',
        ),
        27 => array(
            'Id' => 54,
            'Label' => 'Night view',
        ),
        28 => array(
            'Id' => 55,
            'Label' => 'Night view/portrait',
        ),
        29 => array(
            'Id' => 56,
            'Label' => 'Handheld Night Shot',
        ),
        30 => array(
            'Id' => 57,
            'Label' => '3D Sweep Panorama',
        ),
        31 => array(
            'Id' => 64,
            'Label' => 'Auto 2',
        ),
        32 => array(
            'Id' => 65,
            'Label' => 'Auto 2 (no flash)',
        ),
        33 => array(
            'Id' => 80,
            'Label' => 'Sweep Panorama',
        ),
        34 => array(
            'Id' => 96,
            'Label' => 'Anti Motion Blur',
        ),
        35 => array(
            'Id' => 128,
            'Label' => 'Toy Camera',
        ),
        36 => array(
            'Id' => 129,
            'Label' => 'Pop Color',
        ),
        37 => array(
            'Id' => 130,
            'Label' => 'Posterization',
        ),
        38 => array(
            'Id' => 131,
            'Label' => 'Posterization B/W',
        ),
        39 => array(
            'Id' => 132,
            'Label' => 'Retro Photo',
        ),
        40 => array(
            'Id' => 133,
            'Label' => 'High-key',
        ),
        41 => array(
            'Id' => 134,
            'Label' => 'Partial Color Red',
        ),
        42 => array(
            'Id' => 135,
            'Label' => 'Partial Color Green',
        ),
        43 => array(
            'Id' => 136,
            'Label' => 'Partial Color Blue',
        ),
        44 => array(
            'Id' => 137,
            'Label' => 'Partial Color Yellow',
        ),
        45 => array(
            'Id' => 138,
            'Label' => 'High Contrast Monochrome',
        ),
        46 => array(
            'Id' => 241,
            'Label' => 'Landscape',
        ),
        47 => array(
            'Id' => 243,
            'Label' => 'Aperture-priority AE',
        ),
        48 => array(
            'Id' => 245,
            'Label' => 'Portrait',
        ),
        49 => array(
            'Id' => 246,
            'Label' => 'Auto',
        ),
        50 => array(
            'Id' => 247,
            'Label' => 'Program AE',
        ),
        51 => array(
            'Id' => 249,
            'Label' => 'Macro',
        ),
        52 => array(
            'Id' => 252,
            'Label' => 'Sunset',
        ),
        53 => array(
            'Id' => 253,
            'Label' => 'Sports',
        ),
        54 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
        55 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        56 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        57 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        58 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        59 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        60 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        61 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        62 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        63 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        64 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        65 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        66 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        67 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        68 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        69 => array(
            'Id' => 1,
            'Label' => 'Program AE',
        ),
        70 => array(
            'Id' => 2,
            'Label' => 'Aperture-priority AE',
        ),
        71 => array(
            'Id' => 3,
            'Label' => 'Shutter speed priority AE',
        ),
        72 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        73 => array(
            'Id' => 5,
            'Label' => 'Cont. Priority AE',
        ),
        74 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        75 => array(
            'Id' => 17,
            'Label' => 'Auto (no flash)',
        ),
        76 => array(
            'Id' => 18,
            'Label' => 'Auto+',
        ),
        77 => array(
            'Id' => 49,
            'Label' => 'Portrait',
        ),
        78 => array(
            'Id' => 50,
            'Label' => 'Landscape',
        ),
        79 => array(
            'Id' => 51,
            'Label' => 'Macro',
        ),
        80 => array(
            'Id' => 52,
            'Label' => 'Sports',
        ),
        81 => array(
            'Id' => 53,
            'Label' => 'Sunset',
        ),
        82 => array(
            'Id' => 54,
            'Label' => 'Night view',
        ),
        83 => array(
            'Id' => 55,
            'Label' => 'Night view/portrait',
        ),
        84 => array(
            'Id' => 56,
            'Label' => 'Handheld Night Shot',
        ),
        85 => array(
            'Id' => 57,
            'Label' => '3D Sweep Panorama',
        ),
        86 => array(
            'Id' => 64,
            'Label' => 'Auto 2',
        ),
        87 => array(
            'Id' => 65,
            'Label' => 'Auto 2 (no flash)',
        ),
        88 => array(
            'Id' => 80,
            'Label' => 'Sweep Panorama',
        ),
        89 => array(
            'Id' => 96,
            'Label' => 'Anti Motion Blur',
        ),
        90 => array(
            'Id' => 128,
            'Label' => 'Toy Camera',
        ),
        91 => array(
            'Id' => 129,
            'Label' => 'Pop Color',
        ),
        92 => array(
            'Id' => 130,
            'Label' => 'Posterization',
        ),
        93 => array(
            'Id' => 131,
            'Label' => 'Posterization B/W',
        ),
        94 => array(
            'Id' => 132,
            'Label' => 'Retro Photo',
        ),
        95 => array(
            'Id' => 133,
            'Label' => 'High-key',
        ),
        96 => array(
            'Id' => 134,
            'Label' => 'Partial Color Red',
        ),
        97 => array(
            'Id' => 135,
            'Label' => 'Partial Color Green',
        ),
        98 => array(
            'Id' => 136,
            'Label' => 'Partial Color Blue',
        ),
        99 => array(
            'Id' => 137,
            'Label' => 'Partial Color Yellow',
        ),
        100 => array(
            'Id' => 138,
            'Label' => 'High Contrast Monochrome',
        ),
        101 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        102 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        103 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        104 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        105 => array(
            'Id' => 5,
            'Label' => 'Auto',
        ),
        106 => array(
            'Id' => 7,
            'Label' => 'Auto+',
        ),
        107 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        108 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        109 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        110 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        111 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
    );

    protected $Index = 'mixed';

}
