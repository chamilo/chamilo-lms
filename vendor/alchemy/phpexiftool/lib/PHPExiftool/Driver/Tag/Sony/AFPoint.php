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
            'Label' => 'B4',
        ),
        49 => array(
            'Id' => 1,
            'Label' => 'C4',
        ),
        50 => array(
            'Id' => 2,
            'Label' => 'D4',
        ),
        51 => array(
            'Id' => 3,
            'Label' => 'E4',
        ),
        52 => array(
            'Id' => 4,
            'Label' => 'F4',
        ),
        53 => array(
            'Id' => 5,
            'Label' => 'G4',
        ),
        54 => array(
            'Id' => 6,
            'Label' => 'H4',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'B3',
        ),
        56 => array(
            'Id' => 8,
            'Label' => 'C3',
        ),
        57 => array(
            'Id' => 9,
            'Label' => 'D3',
        ),
        58 => array(
            'Id' => 10,
            'Label' => 'E3',
        ),
        59 => array(
            'Id' => 11,
            'Label' => 'F3',
        ),
        60 => array(
            'Id' => 12,
            'Label' => 'G3',
        ),
        61 => array(
            'Id' => 13,
            'Label' => 'H3',
        ),
        62 => array(
            'Id' => 14,
            'Label' => 'B2',
        ),
        63 => array(
            'Id' => 15,
            'Label' => 'C2',
        ),
        64 => array(
            'Id' => 16,
            'Label' => 'D2',
        ),
        65 => array(
            'Id' => 17,
            'Label' => 'E2',
        ),
        66 => array(
            'Id' => 18,
            'Label' => 'F2',
        ),
        67 => array(
            'Id' => 19,
            'Label' => 'G2',
        ),
        68 => array(
            'Id' => 20,
            'Label' => 'H2',
        ),
        69 => array(
            'Id' => 21,
            'Label' => 'C1',
        ),
        70 => array(
            'Id' => 22,
            'Label' => 'D1',
        ),
        71 => array(
            'Id' => 23,
            'Label' => 'E1',
        ),
        72 => array(
            'Id' => 24,
            'Label' => 'F1',
        ),
        73 => array(
            'Id' => 25,
            'Label' => 'G1',
        ),
        74 => array(
            'Id' => 26,
            'Label' => 'A7 Vertical',
        ),
        75 => array(
            'Id' => 27,
            'Label' => 'A6 Vertical',
        ),
        76 => array(
            'Id' => 28,
            'Label' => 'A5 Vertical',
        ),
        77 => array(
            'Id' => 29,
            'Label' => 'C7 Vertical',
        ),
        78 => array(
            'Id' => 30,
            'Label' => 'C6 Vertical',
        ),
        79 => array(
            'Id' => 31,
            'Label' => 'C5 Vertical',
        ),
        80 => array(
            'Id' => 32,
            'Label' => 'E7 Vertical',
        ),
        81 => array(
            'Id' => 33,
            'Label' => 'E6 Center Vertical',
        ),
        82 => array(
            'Id' => 34,
            'Label' => 'E5 Vertical',
        ),
        83 => array(
            'Id' => 35,
            'Label' => 'G7 Vertical',
        ),
        84 => array(
            'Id' => 36,
            'Label' => 'G6 Vertical',
        ),
        85 => array(
            'Id' => 37,
            'Label' => 'G5 Vertical',
        ),
        86 => array(
            'Id' => 38,
            'Label' => 'I7 Vertical',
        ),
        87 => array(
            'Id' => 39,
            'Label' => 'I6 Vertical',
        ),
        88 => array(
            'Id' => 40,
            'Label' => 'I5 Vertical',
        ),
        89 => array(
            'Id' => 41,
            'Label' => 'A7',
        ),
        90 => array(
            'Id' => 42,
            'Label' => 'B7',
        ),
        91 => array(
            'Id' => 43,
            'Label' => 'C7',
        ),
        92 => array(
            'Id' => 44,
            'Label' => 'D7',
        ),
        93 => array(
            'Id' => 45,
            'Label' => 'E7',
        ),
        94 => array(
            'Id' => 46,
            'Label' => 'F7',
        ),
        95 => array(
            'Id' => 47,
            'Label' => 'G7',
        ),
        96 => array(
            'Id' => 48,
            'Label' => 'H7',
        ),
        97 => array(
            'Id' => 49,
            'Label' => 'I7',
        ),
        98 => array(
            'Id' => 50,
            'Label' => 'A6',
        ),
        99 => array(
            'Id' => 51,
            'Label' => 'B6',
        ),
        100 => array(
            'Id' => 52,
            'Label' => 'C6',
        ),
        101 => array(
            'Id' => 53,
            'Label' => 'D6',
        ),
        102 => array(
            'Id' => 54,
            'Label' => 'E6 Center',
        ),
        103 => array(
            'Id' => 55,
            'Label' => 'F6',
        ),
        104 => array(
            'Id' => 56,
            'Label' => 'G6',
        ),
        105 => array(
            'Id' => 57,
            'Label' => 'H6',
        ),
        106 => array(
            'Id' => 58,
            'Label' => 'I6',
        ),
        107 => array(
            'Id' => 59,
            'Label' => 'A5',
        ),
        108 => array(
            'Id' => 60,
            'Label' => 'B5',
        ),
        109 => array(
            'Id' => 61,
            'Label' => 'C5',
        ),
        110 => array(
            'Id' => 62,
            'Label' => 'D5',
        ),
        111 => array(
            'Id' => 63,
            'Label' => 'E5',
        ),
        112 => array(
            'Id' => 64,
            'Label' => 'F5',
        ),
        113 => array(
            'Id' => 65,
            'Label' => 'G5',
        ),
        114 => array(
            'Id' => 66,
            'Label' => 'H5',
        ),
        115 => array(
            'Id' => 67,
            'Label' => 'I5',
        ),
        116 => array(
            'Id' => 68,
            'Label' => 'C11',
        ),
        117 => array(
            'Id' => 69,
            'Label' => 'D11',
        ),
        118 => array(
            'Id' => 70,
            'Label' => 'E11',
        ),
        119 => array(
            'Id' => 71,
            'Label' => 'F11',
        ),
        120 => array(
            'Id' => 72,
            'Label' => 'G11',
        ),
        121 => array(
            'Id' => 73,
            'Label' => 'B10',
        ),
        122 => array(
            'Id' => 74,
            'Label' => 'C10',
        ),
        123 => array(
            'Id' => 75,
            'Label' => 'D10',
        ),
        124 => array(
            'Id' => 76,
            'Label' => 'E10',
        ),
        125 => array(
            'Id' => 77,
            'Label' => 'F10',
        ),
        126 => array(
            'Id' => 78,
            'Label' => 'G10',
        ),
        127 => array(
            'Id' => 79,
            'Label' => 'H10',
        ),
        128 => array(
            'Id' => 80,
            'Label' => 'B9',
        ),
        129 => array(
            'Id' => 81,
            'Label' => 'C9',
        ),
        130 => array(
            'Id' => 82,
            'Label' => 'D9',
        ),
        131 => array(
            'Id' => 83,
            'Label' => 'E9',
        ),
        132 => array(
            'Id' => 84,
            'Label' => 'F9',
        ),
        133 => array(
            'Id' => 85,
            'Label' => 'G9',
        ),
        134 => array(
            'Id' => 86,
            'Label' => 'H9',
        ),
        135 => array(
            'Id' => 87,
            'Label' => 'B8',
        ),
        136 => array(
            'Id' => 88,
            'Label' => 'C8',
        ),
        137 => array(
            'Id' => 89,
            'Label' => 'D8',
        ),
        138 => array(
            'Id' => 90,
            'Label' => 'E8',
        ),
        139 => array(
            'Id' => 91,
            'Label' => 'F8',
        ),
        140 => array(
            'Id' => 92,
            'Label' => 'G8',
        ),
        141 => array(
            'Id' => 93,
            'Label' => 'H8',
        ),
        142 => array(
            'Id' => 94,
            'Label' => 'E6 Center F2.8',
        ),
        143 => array(
            'Id' => 255,
            'Label' => '(none)',
        ),
        144 => array(
            'Id' => 0,
            'Label' => 'Upper-left',
        ),
        145 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        146 => array(
            'Id' => 2,
            'Label' => 'Lower-left',
        ),
        147 => array(
            'Id' => 3,
            'Label' => 'Far Left',
        ),
        148 => array(
            'Id' => 4,
            'Label' => 'Bottom Assist-left',
        ),
        149 => array(
            'Id' => 5,
            'Label' => 'Bottom',
        ),
        150 => array(
            'Id' => 6,
            'Label' => 'Bottom Assist-right',
        ),
        151 => array(
            'Id' => 7,
            'Label' => 'Center (7)',
        ),
        152 => array(
            'Id' => 8,
            'Label' => 'Center (horizontal)',
        ),
        153 => array(
            'Id' => 9,
            'Label' => 'Center (9)',
        ),
        154 => array(
            'Id' => 10,
            'Label' => 'Center (10)',
        ),
        155 => array(
            'Id' => 11,
            'Label' => 'Center (11)',
        ),
        156 => array(
            'Id' => 12,
            'Label' => 'Center (12)',
        ),
        157 => array(
            'Id' => 13,
            'Label' => 'Center (vertical)',
        ),
        158 => array(
            'Id' => 14,
            'Label' => 'Center (14)',
        ),
        159 => array(
            'Id' => 15,
            'Label' => 'Top Assist-left',
        ),
        160 => array(
            'Id' => 16,
            'Label' => 'Top',
        ),
        161 => array(
            'Id' => 17,
            'Label' => 'Top Assist-right',
        ),
        162 => array(
            'Id' => 18,
            'Label' => 'Far Right',
        ),
        163 => array(
            'Id' => 19,
            'Label' => 'Upper-right',
        ),
        164 => array(
            'Id' => 20,
            'Label' => 'Right',
        ),
        165 => array(
            'Id' => 21,
            'Label' => 'Lower-right',
        ),
        166 => array(
            'Id' => 22,
            'Label' => 'Center F2.8',
        ),
        167 => array(
            'Id' => 0,
            'Label' => 'Top-right',
        ),
        168 => array(
            'Id' => 1,
            'Label' => 'Bottom-right',
        ),
        169 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        170 => array(
            'Id' => 3,
            'Label' => 'Middle Horizontal',
        ),
        171 => array(
            'Id' => 4,
            'Label' => 'Center Vertical',
        ),
        172 => array(
            'Id' => 5,
            'Label' => 'Top',
        ),
        173 => array(
            'Id' => 6,
            'Label' => 'Top-left',
        ),
        174 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
        175 => array(
            'Id' => 0,
            'Label' => 'Top-right',
        ),
        176 => array(
            'Id' => 1,
            'Label' => 'Bottom-right',
        ),
        177 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        178 => array(
            'Id' => 3,
            'Label' => 'Middle Horizontal',
        ),
        179 => array(
            'Id' => 4,
            'Label' => 'Center Vertical',
        ),
        180 => array(
            'Id' => 5,
            'Label' => 'Top',
        ),
        181 => array(
            'Id' => 6,
            'Label' => 'Top-left',
        ),
        182 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
        183 => array(
            'Id' => 0,
            'Label' => 'Upper-left',
        ),
        184 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        185 => array(
            'Id' => 2,
            'Label' => 'Lower-left',
        ),
        186 => array(
            'Id' => 3,
            'Label' => 'Far Left',
        ),
        187 => array(
            'Id' => 4,
            'Label' => 'Top (horizontal)',
        ),
        188 => array(
            'Id' => 5,
            'Label' => 'Near Right',
        ),
        189 => array(
            'Id' => 6,
            'Label' => 'Center (horizontal)',
        ),
        190 => array(
            'Id' => 7,
            'Label' => 'Near Left',
        ),
        191 => array(
            'Id' => 8,
            'Label' => 'Bottom (horizontal)',
        ),
        192 => array(
            'Id' => 9,
            'Label' => 'Top (vertical)',
        ),
        193 => array(
            'Id' => 10,
            'Label' => 'Center (vertical)',
        ),
        194 => array(
            'Id' => 11,
            'Label' => 'Bottom (vertical)',
        ),
        195 => array(
            'Id' => 12,
            'Label' => 'Far Right',
        ),
        196 => array(
            'Id' => 13,
            'Label' => 'Upper-right',
        ),
        197 => array(
            'Id' => 14,
            'Label' => 'Right',
        ),
        198 => array(
            'Id' => 15,
            'Label' => 'Lower-right',
        ),
        199 => array(
            'Id' => 16,
            'Label' => 'Upper-middle',
        ),
        200 => array(
            'Id' => 17,
            'Label' => 'Lower-middle',
        ),
        201 => array(
            'Id' => 255,
            'Label' => '(none)',
        ),
    );

    protected $Index = 'mixed';

}
