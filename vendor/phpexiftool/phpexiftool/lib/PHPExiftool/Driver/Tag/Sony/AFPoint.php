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
class AFPoint extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPoint';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Upper-left',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Lower-left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Far Left',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Top (horizontal)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Near Right',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Center (horizontal)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Near Left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Bottom (horizontal)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Top (vertical)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Center (vertical)',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Bottom (vertical)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Far Right',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Upper-right',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Right',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Lower-right',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Upper-middle',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Lower-middle',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Upper Far Left',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Upper-left (horizontal)',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'Far Left (horizontal)',
        ),
        21 => array(
            'Id' => 3,
            'Label' => 'Left (horizontal)',
        ),
        22 => array(
            'Id' => 4,
            'Label' => 'Lower Far Left',
        ),
        23 => array(
            'Id' => 5,
            'Label' => 'Lower-left (horizontal)',
        ),
        24 => array(
            'Id' => 6,
            'Label' => 'Upper-left (vertical)',
        ),
        25 => array(
            'Id' => 7,
            'Label' => 'Left (vertical)',
        ),
        26 => array(
            'Id' => 8,
            'Label' => 'Lower-left (vertical)',
        ),
        27 => array(
            'Id' => 9,
            'Label' => 'Far Left (vertical)',
        ),
        28 => array(
            'Id' => 10,
            'Label' => 'Top (horizontal)',
        ),
        29 => array(
            'Id' => 11,
            'Label' => 'Near Right',
        ),
        30 => array(
            'Id' => 12,
            'Label' => 'Center (horizontal)',
        ),
        31 => array(
            'Id' => 13,
            'Label' => 'Near Left',
        ),
        32 => array(
            'Id' => 14,
            'Label' => 'Bottom (horizontal)',
        ),
        33 => array(
            'Id' => 15,
            'Label' => 'Top (vertical)',
        ),
        34 => array(
            'Id' => 16,
            'Label' => 'Upper-middle',
        ),
        35 => array(
            'Id' => 17,
            'Label' => 'Center (vertical)',
        ),
        36 => array(
            'Id' => 18,
            'Label' => 'Lower-middle',
        ),
        37 => array(
            'Id' => 19,
            'Label' => 'Bottom (vertical)',
        ),
        38 => array(
            'Id' => 20,
            'Label' => 'Upper Far Right',
        ),
        39 => array(
            'Id' => 21,
            'Label' => 'Upper-right (horizontal)',
        ),
        40 => array(
            'Id' => 22,
            'Label' => 'Far Right (horizontal)',
        ),
        41 => array(
            'Id' => 23,
            'Label' => 'Right (horizontal)',
        ),
        42 => array(
            'Id' => 24,
            'Label' => 'Lower Far Right',
        ),
        43 => array(
            'Id' => 25,
            'Label' => 'Lower-right (horizontal)',
        ),
        44 => array(
            'Id' => 26,
            'Label' => 'Far Right (vertical)',
        ),
        45 => array(
            'Id' => 27,
            'Label' => 'Upper-right (vertical)',
        ),
        46 => array(
            'Id' => 28,
            'Label' => 'Right (vertical)',
        ),
        47 => array(
            'Id' => 29,
            'Label' => 'Lower-right (vertical)',
        ),
        48 => array(
            'Id' => 0,
            'Label' => 'Upper-left',
        ),
        49 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        50 => array(
            'Id' => 2,
            'Label' => 'Lower-left',
        ),
        51 => array(
            'Id' => 3,
            'Label' => 'Far Left',
        ),
        52 => array(
            'Id' => 4,
            'Label' => 'Bottom Assist-left',
        ),
        53 => array(
            'Id' => 5,
            'Label' => 'Bottom',
        ),
        54 => array(
            'Id' => 6,
            'Label' => 'Bottom Assist-right',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'Center (7)',
        ),
        56 => array(
            'Id' => 8,
            'Label' => 'Center (horizontal)',
        ),
        57 => array(
            'Id' => 9,
            'Label' => 'Center (9)',
        ),
        58 => array(
            'Id' => 10,
            'Label' => 'Center (10)',
        ),
        59 => array(
            'Id' => 11,
            'Label' => 'Center (11)',
        ),
        60 => array(
            'Id' => 12,
            'Label' => 'Center (12)',
        ),
        61 => array(
            'Id' => 13,
            'Label' => 'Center (vertical)',
        ),
        62 => array(
            'Id' => 14,
            'Label' => 'Center (14)',
        ),
        63 => array(
            'Id' => 15,
            'Label' => 'Top Assist-left',
        ),
        64 => array(
            'Id' => 16,
            'Label' => 'Top',
        ),
        65 => array(
            'Id' => 17,
            'Label' => 'Top Assist-right',
        ),
        66 => array(
            'Id' => 18,
            'Label' => 'Far Right',
        ),
        67 => array(
            'Id' => 19,
            'Label' => 'Upper-right',
        ),
        68 => array(
            'Id' => 20,
            'Label' => 'Right',
        ),
        69 => array(
            'Id' => 21,
            'Label' => 'Lower-right',
        ),
        70 => array(
            'Id' => 22,
            'Label' => 'Center F2.8',
        ),
        71 => array(
            'Id' => 0,
            'Label' => 'Top-right',
        ),
        72 => array(
            'Id' => 1,
            'Label' => 'Bottom-right',
        ),
        73 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        74 => array(
            'Id' => 3,
            'Label' => 'Middle Horizontal',
        ),
        75 => array(
            'Id' => 4,
            'Label' => 'Center Vertical',
        ),
        76 => array(
            'Id' => 5,
            'Label' => 'Top',
        ),
        77 => array(
            'Id' => 6,
            'Label' => 'Top-left',
        ),
        78 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
        79 => array(
            'Id' => 0,
            'Label' => 'Top-right',
        ),
        80 => array(
            'Id' => 1,
            'Label' => 'Bottom-right',
        ),
        81 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        82 => array(
            'Id' => 3,
            'Label' => 'Middle Horizontal',
        ),
        83 => array(
            'Id' => 4,
            'Label' => 'Center Vertical',
        ),
        84 => array(
            'Id' => 5,
            'Label' => 'Top',
        ),
        85 => array(
            'Id' => 6,
            'Label' => 'Top-left',
        ),
        86 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
        87 => array(
            'Id' => 0,
            'Label' => 'Upper-left',
        ),
        88 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        89 => array(
            'Id' => 2,
            'Label' => 'Lower-left',
        ),
        90 => array(
            'Id' => 3,
            'Label' => 'Far Left',
        ),
        91 => array(
            'Id' => 4,
            'Label' => 'Top (horizontal)',
        ),
        92 => array(
            'Id' => 5,
            'Label' => 'Near Right',
        ),
        93 => array(
            'Id' => 6,
            'Label' => 'Center (horizontal)',
        ),
        94 => array(
            'Id' => 7,
            'Label' => 'Near Left',
        ),
        95 => array(
            'Id' => 8,
            'Label' => 'Bottom (horizontal)',
        ),
        96 => array(
            'Id' => 9,
            'Label' => 'Top (vertical)',
        ),
        97 => array(
            'Id' => 10,
            'Label' => 'Center (vertical)',
        ),
        98 => array(
            'Id' => 11,
            'Label' => 'Bottom (vertical)',
        ),
        99 => array(
            'Id' => 12,
            'Label' => 'Far Right',
        ),
        100 => array(
            'Id' => 13,
            'Label' => 'Upper-right',
        ),
        101 => array(
            'Id' => 14,
            'Label' => 'Right',
        ),
        102 => array(
            'Id' => 15,
            'Label' => 'Lower-right',
        ),
        103 => array(
            'Id' => 16,
            'Label' => 'Upper-middle',
        ),
        104 => array(
            'Id' => 17,
            'Label' => 'Lower-middle',
        ),
        105 => array(
            'Id' => 255,
            'Label' => '(none)',
        ),
    );

    protected $Index = 'mixed';

}
