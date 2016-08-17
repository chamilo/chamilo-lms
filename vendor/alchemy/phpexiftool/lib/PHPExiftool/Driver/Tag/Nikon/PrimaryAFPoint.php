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
class PrimaryAFPoint extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'PrimaryAFPoint';

    protected $FullName = 'Nikon::AFInfo2';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Primary AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'C6 (Center)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'B6',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'A5',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'D6',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'E5',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'C7',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'B7',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'A6',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'D7',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'E6',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'C5',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'B5',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'A4',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'D5',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'E4',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'C8',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'B8',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'A7',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'D8',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'E7',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'C9',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'B9',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'A8',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'D9',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'E8',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'C10',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'B10',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'A9',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'D10',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'E9',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'C11',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'B11',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'D11',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'C4',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'B4',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'A3',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'D4',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'E3',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'C3',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'B3',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'A2',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'D3',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'E2',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'C2',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'B2',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'A1',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'D2',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'E1',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'C1',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'B1',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'D1',
        ),
        52 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        53 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        54 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        55 => array(
            'Id' => 3,
            'Label' => 'Bottom',
        ),
        56 => array(
            'Id' => 4,
            'Label' => 'Mid-left',
        ),
        57 => array(
            'Id' => 5,
            'Label' => 'Upper-left',
        ),
        58 => array(
            'Id' => 6,
            'Label' => 'Lower-left',
        ),
        59 => array(
            'Id' => 7,
            'Label' => 'Far Left',
        ),
        60 => array(
            'Id' => 8,
            'Label' => 'Mid-right',
        ),
        61 => array(
            'Id' => 9,
            'Label' => 'Upper-right',
        ),
        62 => array(
            'Id' => 10,
            'Label' => 'Lower-right',
        ),
        63 => array(
            'Id' => 11,
            'Label' => 'Far Right',
        ),
        64 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        65 => array(
            'Id' => 1,
            'Label' => 'C6 (Center)',
        ),
        66 => array(
            'Id' => 2,
            'Label' => 'B6',
        ),
        67 => array(
            'Id' => 3,
            'Label' => 'A2',
        ),
        68 => array(
            'Id' => 4,
            'Label' => 'D6',
        ),
        69 => array(
            'Id' => 5,
            'Label' => 'E2',
        ),
        70 => array(
            'Id' => 6,
            'Label' => 'C7',
        ),
        71 => array(
            'Id' => 7,
            'Label' => 'B7',
        ),
        72 => array(
            'Id' => 8,
            'Label' => 'A3',
        ),
        73 => array(
            'Id' => 9,
            'Label' => 'D7',
        ),
        74 => array(
            'Id' => 10,
            'Label' => 'E3',
        ),
        75 => array(
            'Id' => 11,
            'Label' => 'C5',
        ),
        76 => array(
            'Id' => 12,
            'Label' => 'B5',
        ),
        77 => array(
            'Id' => 13,
            'Label' => 'A1',
        ),
        78 => array(
            'Id' => 14,
            'Label' => 'D5',
        ),
        79 => array(
            'Id' => 15,
            'Label' => 'E1',
        ),
        80 => array(
            'Id' => 16,
            'Label' => 'C8',
        ),
        81 => array(
            'Id' => 17,
            'Label' => 'B8',
        ),
        82 => array(
            'Id' => 18,
            'Label' => 'D8',
        ),
        83 => array(
            'Id' => 19,
            'Label' => 'C9',
        ),
        84 => array(
            'Id' => 20,
            'Label' => 'B9',
        ),
        85 => array(
            'Id' => 21,
            'Label' => 'D9',
        ),
        86 => array(
            'Id' => 22,
            'Label' => 'C10',
        ),
        87 => array(
            'Id' => 23,
            'Label' => 'B10',
        ),
        88 => array(
            'Id' => 24,
            'Label' => 'D10',
        ),
        89 => array(
            'Id' => 25,
            'Label' => 'C11',
        ),
        90 => array(
            'Id' => 26,
            'Label' => 'B11',
        ),
        91 => array(
            'Id' => 27,
            'Label' => 'D11',
        ),
        92 => array(
            'Id' => 28,
            'Label' => 'C4',
        ),
        93 => array(
            'Id' => 29,
            'Label' => 'B4',
        ),
        94 => array(
            'Id' => 30,
            'Label' => 'D4',
        ),
        95 => array(
            'Id' => 31,
            'Label' => 'C3',
        ),
        96 => array(
            'Id' => 32,
            'Label' => 'B3',
        ),
        97 => array(
            'Id' => 33,
            'Label' => 'D3',
        ),
        98 => array(
            'Id' => 34,
            'Label' => 'C2',
        ),
        99 => array(
            'Id' => 35,
            'Label' => 'B2',
        ),
        100 => array(
            'Id' => 36,
            'Label' => 'D2',
        ),
        101 => array(
            'Id' => 37,
            'Label' => 'C1',
        ),
        102 => array(
            'Id' => 38,
            'Label' => 'B1',
        ),
        103 => array(
            'Id' => 39,
            'Label' => 'D1',
        ),
        104 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        105 => array(
            'Id' => 1,
            'Label' => 'E8 (Center)',
        ),
        106 => array(
            'Id' => 2,
            'Label' => 'D8',
        ),
        107 => array(
            'Id' => 3,
            'Label' => 'C8',
        ),
        108 => array(
            'Id' => 4,
            'Label' => 'B8',
        ),
        109 => array(
            'Id' => 5,
            'Label' => 'A8',
        ),
        110 => array(
            'Id' => 6,
            'Label' => 'F8',
        ),
        111 => array(
            'Id' => 7,
            'Label' => 'G8',
        ),
        112 => array(
            'Id' => 8,
            'Label' => 'H8',
        ),
        113 => array(
            'Id' => 9,
            'Label' => 'I8',
        ),
        114 => array(
            'Id' => 10,
            'Label' => 'E9',
        ),
        115 => array(
            'Id' => 11,
            'Label' => 'D9',
        ),
        116 => array(
            'Id' => 12,
            'Label' => 'C9',
        ),
        117 => array(
            'Id' => 13,
            'Label' => 'B9',
        ),
        118 => array(
            'Id' => 14,
            'Label' => 'A9',
        ),
        119 => array(
            'Id' => 15,
            'Label' => 'F9',
        ),
        120 => array(
            'Id' => 16,
            'Label' => 'G9',
        ),
        121 => array(
            'Id' => 17,
            'Label' => 'H9',
        ),
        122 => array(
            'Id' => 18,
            'Label' => 'I9',
        ),
        123 => array(
            'Id' => 19,
            'Label' => 'E7',
        ),
        124 => array(
            'Id' => 20,
            'Label' => 'D7',
        ),
        125 => array(
            'Id' => 21,
            'Label' => 'C7',
        ),
        126 => array(
            'Id' => 22,
            'Label' => 'B7',
        ),
        127 => array(
            'Id' => 23,
            'Label' => 'A7',
        ),
        128 => array(
            'Id' => 24,
            'Label' => 'F7',
        ),
        129 => array(
            'Id' => 25,
            'Label' => 'G7',
        ),
        130 => array(
            'Id' => 26,
            'Label' => 'H7',
        ),
        131 => array(
            'Id' => 27,
            'Label' => 'I7',
        ),
        132 => array(
            'Id' => 28,
            'Label' => 'E10',
        ),
        133 => array(
            'Id' => 29,
            'Label' => 'D10',
        ),
        134 => array(
            'Id' => 30,
            'Label' => 'C10',
        ),
        135 => array(
            'Id' => 31,
            'Label' => 'B10',
        ),
        136 => array(
            'Id' => 32,
            'Label' => 'A10',
        ),
        137 => array(
            'Id' => 33,
            'Label' => 'F10',
        ),
        138 => array(
            'Id' => 34,
            'Label' => 'G10',
        ),
        139 => array(
            'Id' => 35,
            'Label' => 'H10',
        ),
        140 => array(
            'Id' => 36,
            'Label' => 'I10',
        ),
        141 => array(
            'Id' => 37,
            'Label' => 'E11',
        ),
        142 => array(
            'Id' => 38,
            'Label' => 'D11',
        ),
        143 => array(
            'Id' => 39,
            'Label' => 'C11',
        ),
        144 => array(
            'Id' => 40,
            'Label' => 'B11',
        ),
        145 => array(
            'Id' => 41,
            'Label' => 'A11',
        ),
        146 => array(
            'Id' => 42,
            'Label' => 'F11',
        ),
        147 => array(
            'Id' => 43,
            'Label' => 'G11',
        ),
        148 => array(
            'Id' => 44,
            'Label' => 'H11',
        ),
        149 => array(
            'Id' => 45,
            'Label' => 'I11',
        ),
        150 => array(
            'Id' => 46,
            'Label' => 'E12',
        ),
        151 => array(
            'Id' => 47,
            'Label' => 'D12',
        ),
        152 => array(
            'Id' => 48,
            'Label' => 'C12',
        ),
        153 => array(
            'Id' => 49,
            'Label' => 'B12',
        ),
        154 => array(
            'Id' => 50,
            'Label' => 'A12',
        ),
        155 => array(
            'Id' => 51,
            'Label' => 'F12',
        ),
        156 => array(
            'Id' => 52,
            'Label' => 'G12',
        ),
        157 => array(
            'Id' => 53,
            'Label' => 'H12',
        ),
        158 => array(
            'Id' => 54,
            'Label' => 'I12',
        ),
        159 => array(
            'Id' => 55,
            'Label' => 'E13',
        ),
        160 => array(
            'Id' => 56,
            'Label' => 'D13',
        ),
        161 => array(
            'Id' => 57,
            'Label' => 'C13',
        ),
        162 => array(
            'Id' => 58,
            'Label' => 'B13',
        ),
        163 => array(
            'Id' => 59,
            'Label' => 'A13',
        ),
        164 => array(
            'Id' => 60,
            'Label' => 'F13',
        ),
        165 => array(
            'Id' => 61,
            'Label' => 'G13',
        ),
        166 => array(
            'Id' => 62,
            'Label' => 'H13',
        ),
        167 => array(
            'Id' => 63,
            'Label' => 'I13',
        ),
        168 => array(
            'Id' => 64,
            'Label' => 'E14',
        ),
        169 => array(
            'Id' => 65,
            'Label' => 'D14',
        ),
        170 => array(
            'Id' => 66,
            'Label' => 'C14',
        ),
        171 => array(
            'Id' => 67,
            'Label' => 'B14',
        ),
        172 => array(
            'Id' => 68,
            'Label' => 'A14',
        ),
        173 => array(
            'Id' => 69,
            'Label' => 'F14',
        ),
        174 => array(
            'Id' => 70,
            'Label' => 'G14',
        ),
        175 => array(
            'Id' => 71,
            'Label' => 'H14',
        ),
        176 => array(
            'Id' => 72,
            'Label' => 'I14',
        ),
        177 => array(
            'Id' => 73,
            'Label' => 'E15',
        ),
        178 => array(
            'Id' => 74,
            'Label' => 'D15',
        ),
        179 => array(
            'Id' => 75,
            'Label' => 'C15',
        ),
        180 => array(
            'Id' => 76,
            'Label' => 'B15',
        ),
        181 => array(
            'Id' => 77,
            'Label' => 'A15',
        ),
        182 => array(
            'Id' => 78,
            'Label' => 'F15',
        ),
        183 => array(
            'Id' => 79,
            'Label' => 'G15',
        ),
        184 => array(
            'Id' => 80,
            'Label' => 'H15',
        ),
        185 => array(
            'Id' => 81,
            'Label' => 'I15',
        ),
        186 => array(
            'Id' => 82,
            'Label' => 'E6',
        ),
        187 => array(
            'Id' => 83,
            'Label' => 'D6',
        ),
        188 => array(
            'Id' => 84,
            'Label' => 'C6',
        ),
        189 => array(
            'Id' => 85,
            'Label' => 'B6',
        ),
        190 => array(
            'Id' => 86,
            'Label' => 'A6',
        ),
        191 => array(
            'Id' => 87,
            'Label' => 'F6',
        ),
        192 => array(
            'Id' => 88,
            'Label' => 'G6',
        ),
        193 => array(
            'Id' => 89,
            'Label' => 'H6',
        ),
        194 => array(
            'Id' => 90,
            'Label' => 'I6',
        ),
        195 => array(
            'Id' => 91,
            'Label' => 'E5',
        ),
        196 => array(
            'Id' => 92,
            'Label' => 'D5',
        ),
        197 => array(
            'Id' => 93,
            'Label' => 'C5',
        ),
        198 => array(
            'Id' => 94,
            'Label' => 'B5',
        ),
        199 => array(
            'Id' => 95,
            'Label' => 'A5',
        ),
        200 => array(
            'Id' => 96,
            'Label' => 'F5',
        ),
        201 => array(
            'Id' => 97,
            'Label' => 'G5',
        ),
        202 => array(
            'Id' => 98,
            'Label' => 'H5',
        ),
        203 => array(
            'Id' => 99,
            'Label' => 'I5',
        ),
        204 => array(
            'Id' => 100,
            'Label' => 'E4',
        ),
        205 => array(
            'Id' => 101,
            'Label' => 'D4',
        ),
        206 => array(
            'Id' => 102,
            'Label' => 'C4',
        ),
        207 => array(
            'Id' => 103,
            'Label' => 'B4',
        ),
        208 => array(
            'Id' => 104,
            'Label' => 'A4',
        ),
        209 => array(
            'Id' => 105,
            'Label' => 'F4',
        ),
        210 => array(
            'Id' => 106,
            'Label' => 'G4',
        ),
        211 => array(
            'Id' => 107,
            'Label' => 'H4',
        ),
        212 => array(
            'Id' => 108,
            'Label' => 'I4',
        ),
        213 => array(
            'Id' => 109,
            'Label' => 'E3',
        ),
        214 => array(
            'Id' => 110,
            'Label' => 'D3',
        ),
        215 => array(
            'Id' => 111,
            'Label' => 'C3',
        ),
        216 => array(
            'Id' => 112,
            'Label' => 'B3',
        ),
        217 => array(
            'Id' => 113,
            'Label' => 'A3',
        ),
        218 => array(
            'Id' => 114,
            'Label' => 'F3',
        ),
        219 => array(
            'Id' => 115,
            'Label' => 'G3',
        ),
        220 => array(
            'Id' => 116,
            'Label' => 'H3',
        ),
        221 => array(
            'Id' => 117,
            'Label' => 'I3',
        ),
        222 => array(
            'Id' => 118,
            'Label' => 'E2',
        ),
        223 => array(
            'Id' => 119,
            'Label' => 'D2',
        ),
        224 => array(
            'Id' => 120,
            'Label' => 'C2',
        ),
        225 => array(
            'Id' => 121,
            'Label' => 'B2',
        ),
        226 => array(
            'Id' => 122,
            'Label' => 'A2',
        ),
        227 => array(
            'Id' => 123,
            'Label' => 'F2',
        ),
        228 => array(
            'Id' => 124,
            'Label' => 'G2',
        ),
        229 => array(
            'Id' => 125,
            'Label' => 'H2',
        ),
        230 => array(
            'Id' => 126,
            'Label' => 'I2',
        ),
        231 => array(
            'Id' => 127,
            'Label' => 'E1',
        ),
        232 => array(
            'Id' => 128,
            'Label' => 'D1',
        ),
        233 => array(
            'Id' => 129,
            'Label' => 'C1',
        ),
        234 => array(
            'Id' => 130,
            'Label' => 'B1',
        ),
        235 => array(
            'Id' => 131,
            'Label' => 'A1',
        ),
        236 => array(
            'Id' => 132,
            'Label' => 'F1',
        ),
        237 => array(
            'Id' => 133,
            'Label' => 'G1',
        ),
        238 => array(
            'Id' => 134,
            'Label' => 'H1',
        ),
        239 => array(
            'Id' => 135,
            'Label' => 'I1',
        ),
        240 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        241 => array(
            'Id' => 82,
            'Label' => 'F8 (Center)',
        ),
        242 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        243 => array(
            'Id' => 115,
            'Label' => 'F11 (Center)',
        ),
        244 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        245 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
    );

    protected $Index = 'mixed';

}
