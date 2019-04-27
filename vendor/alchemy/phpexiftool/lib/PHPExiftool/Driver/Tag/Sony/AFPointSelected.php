<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class AFPointSelected extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointSelected';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point Selected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        16 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        17 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        18 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        19 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        20 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        21 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        25 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        26 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        27 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        28 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        29 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        30 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        31 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        33 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        34 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        35 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        36 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        37 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        38 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        39 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        40 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        41 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        42 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        43 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        44 => array(
            'Id' => 12,
            'Label' => 'Upper-middle',
        ),
        45 => array(
            'Id' => 13,
            'Label' => 'Near Right',
        ),
        46 => array(
            'Id' => 14,
            'Label' => 'Lower-middle',
        ),
        47 => array(
            'Id' => 15,
            'Label' => 'Near Left',
        ),
        48 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        49 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        50 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        51 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        52 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        53 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        54 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        56 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        57 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        58 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        59 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        60 => array(
            'Id' => 12,
            'Label' => 'Upper-middle',
        ),
        61 => array(
            'Id' => 13,
            'Label' => 'Near Right',
        ),
        62 => array(
            'Id' => 14,
            'Label' => 'Lower-middle',
        ),
        63 => array(
            'Id' => 15,
            'Label' => 'Near Left',
        ),
        64 => array(
            'Id' => 16,
            'Label' => 'Upper Far Right',
        ),
        65 => array(
            'Id' => 17,
            'Label' => 'Lower Far Right',
        ),
        66 => array(
            'Id' => 18,
            'Label' => 'Lower Far Left',
        ),
        67 => array(
            'Id' => 19,
            'Label' => 'Upper Far Left',
        ),
        68 => array(
            'Id' => '-1',
            'Label' => 'Auto',
        ),
        69 => array(
            'Id' => 0,
            'Label' => 'A5',
        ),
        70 => array(
            'Id' => 1,
            'Label' => 'A6',
        ),
        71 => array(
            'Id' => 2,
            'Label' => 'A7',
        ),
        72 => array(
            'Id' => 3,
            'Label' => 'B2',
        ),
        73 => array(
            'Id' => 4,
            'Label' => 'B3',
        ),
        74 => array(
            'Id' => 5,
            'Label' => 'B4',
        ),
        75 => array(
            'Id' => 6,
            'Label' => 'B5',
        ),
        76 => array(
            'Id' => 7,
            'Label' => 'B6',
        ),
        77 => array(
            'Id' => 8,
            'Label' => 'B7',
        ),
        78 => array(
            'Id' => 9,
            'Label' => 'B8',
        ),
        79 => array(
            'Id' => 10,
            'Label' => 'B9',
        ),
        80 => array(
            'Id' => 11,
            'Label' => 'B10',
        ),
        81 => array(
            'Id' => 12,
            'Label' => 'C1',
        ),
        82 => array(
            'Id' => 13,
            'Label' => 'C2',
        ),
        83 => array(
            'Id' => 14,
            'Label' => 'C3',
        ),
        84 => array(
            'Id' => 15,
            'Label' => 'C4',
        ),
        85 => array(
            'Id' => 16,
            'Label' => 'C5',
        ),
        86 => array(
            'Id' => 17,
            'Label' => 'C6',
        ),
        87 => array(
            'Id' => 18,
            'Label' => 'C7',
        ),
        88 => array(
            'Id' => 19,
            'Label' => 'C8',
        ),
        89 => array(
            'Id' => 20,
            'Label' => 'C9',
        ),
        90 => array(
            'Id' => 21,
            'Label' => 'C10',
        ),
        91 => array(
            'Id' => 22,
            'Label' => 'C11',
        ),
        92 => array(
            'Id' => 23,
            'Label' => 'D1',
        ),
        93 => array(
            'Id' => 24,
            'Label' => 'D2',
        ),
        94 => array(
            'Id' => 25,
            'Label' => 'D3',
        ),
        95 => array(
            'Id' => 26,
            'Label' => 'D4',
        ),
        96 => array(
            'Id' => 27,
            'Label' => 'D5',
        ),
        97 => array(
            'Id' => 28,
            'Label' => 'D6',
        ),
        98 => array(
            'Id' => 29,
            'Label' => 'D7',
        ),
        99 => array(
            'Id' => 30,
            'Label' => 'D8',
        ),
        100 => array(
            'Id' => 31,
            'Label' => 'D9',
        ),
        101 => array(
            'Id' => 32,
            'Label' => 'D10',
        ),
        102 => array(
            'Id' => 33,
            'Label' => 'D11',
        ),
        103 => array(
            'Id' => 34,
            'Label' => 'E1',
        ),
        104 => array(
            'Id' => 35,
            'Label' => 'E2',
        ),
        105 => array(
            'Id' => 36,
            'Label' => 'E3',
        ),
        106 => array(
            'Id' => 37,
            'Label' => 'E4',
        ),
        107 => array(
            'Id' => 38,
            'Label' => 'E5',
        ),
        108 => array(
            'Id' => 39,
            'Label' => 'E6 (Center)',
        ),
        109 => array(
            'Id' => 40,
            'Label' => 'E7',
        ),
        110 => array(
            'Id' => 41,
            'Label' => 'E8',
        ),
        111 => array(
            'Id' => 42,
            'Label' => 'E9',
        ),
        112 => array(
            'Id' => 43,
            'Label' => 'E10',
        ),
        113 => array(
            'Id' => 44,
            'Label' => 'E11',
        ),
        114 => array(
            'Id' => 45,
            'Label' => 'F1',
        ),
        115 => array(
            'Id' => 46,
            'Label' => 'F2',
        ),
        116 => array(
            'Id' => 47,
            'Label' => 'F3',
        ),
        117 => array(
            'Id' => 48,
            'Label' => 'F4',
        ),
        118 => array(
            'Id' => 49,
            'Label' => 'F5',
        ),
        119 => array(
            'Id' => 50,
            'Label' => 'F6',
        ),
        120 => array(
            'Id' => 51,
            'Label' => 'F7',
        ),
        121 => array(
            'Id' => 52,
            'Label' => 'F8',
        ),
        122 => array(
            'Id' => 53,
            'Label' => 'F9',
        ),
        123 => array(
            'Id' => 54,
            'Label' => 'F10',
        ),
        124 => array(
            'Id' => 55,
            'Label' => 'F11',
        ),
        125 => array(
            'Id' => 56,
            'Label' => 'G1',
        ),
        126 => array(
            'Id' => 57,
            'Label' => 'G2',
        ),
        127 => array(
            'Id' => 58,
            'Label' => 'G3',
        ),
        128 => array(
            'Id' => 59,
            'Label' => 'G4',
        ),
        129 => array(
            'Id' => 60,
            'Label' => 'G5',
        ),
        130 => array(
            'Id' => 61,
            'Label' => 'G6',
        ),
        131 => array(
            'Id' => 62,
            'Label' => 'G7',
        ),
        132 => array(
            'Id' => 63,
            'Label' => 'G8',
        ),
        133 => array(
            'Id' => 64,
            'Label' => 'G9',
        ),
        134 => array(
            'Id' => 65,
            'Label' => 'G10',
        ),
        135 => array(
            'Id' => 66,
            'Label' => 'G11',
        ),
        136 => array(
            'Id' => 67,
            'Label' => 'H2',
        ),
        137 => array(
            'Id' => 68,
            'Label' => 'H3',
        ),
        138 => array(
            'Id' => 69,
            'Label' => 'H4',
        ),
        139 => array(
            'Id' => 70,
            'Label' => 'H5',
        ),
        140 => array(
            'Id' => 71,
            'Label' => 'H6',
        ),
        141 => array(
            'Id' => 72,
            'Label' => 'H7',
        ),
        142 => array(
            'Id' => 73,
            'Label' => 'H8',
        ),
        143 => array(
            'Id' => 74,
            'Label' => 'H9',
        ),
        144 => array(
            'Id' => 75,
            'Label' => 'H10',
        ),
        145 => array(
            'Id' => 76,
            'Label' => 'I5',
        ),
        146 => array(
            'Id' => 77,
            'Label' => 'I6',
        ),
        147 => array(
            'Id' => 78,
            'Label' => 'I7',
        ),
    );

    protected $Index = 'mixed';

}
