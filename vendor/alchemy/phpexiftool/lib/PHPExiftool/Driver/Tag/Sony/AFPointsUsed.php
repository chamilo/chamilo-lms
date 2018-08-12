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
class AFPointsUsed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointsUsed';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'AF Points Used';

    protected $flag_Permanent = true;

    protected $MaxLength = 10;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'G9',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'G10',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'A7',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'B2',
        ),
        5 => array(
            'Id' => 16,
            'Label' => 'B3',
        ),
        6 => array(
            'Id' => 32,
            'Label' => 'B4',
        ),
        7 => array(
            'Id' => 64,
            'Label' => 'H5',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'H6',
        ),
        9 => array(
            'Id' => 256,
            'Label' => 'B7',
        ),
        10 => array(
            'Id' => 512,
            'Label' => 'B8',
        ),
        11 => array(
            'Id' => 1024,
            'Label' => 'H9',
        ),
        12 => array(
            'Id' => 2048,
            'Label' => 'B10',
        ),
        13 => array(
            'Id' => 4096,
            'Label' => 'I5',
        ),
        14 => array(
            'Id' => 8192,
            'Label' => 'I6',
        ),
        15 => array(
            'Id' => 16384,
            'Label' => 'I7',
        ),
        16 => array(
            'Id' => 32768,
            'Label' => 'C4',
        ),
        17 => array(
            'Id' => 65536,
            'Label' => 'C5',
        ),
        18 => array(
            'Id' => 131072,
            'Label' => 'C6',
        ),
        19 => array(
            'Id' => 262144,
            'Label' => 'C7',
        ),
        20 => array(
            'Id' => 524288,
            'Label' => 'C8',
        ),
        21 => array(
            'Id' => 1048576,
            'Label' => 'C9',
        ),
        22 => array(
            'Id' => 2097152,
            'Label' => 'C10',
        ),
        23 => array(
            'Id' => 4194304,
            'Label' => 'C11',
        ),
        24 => array(
            'Id' => 8388608,
            'Label' => 'D1',
        ),
        25 => array(
            'Id' => 16777216,
            'Label' => 'D2',
        ),
        26 => array(
            'Id' => 33554432,
            'Label' => 'D3',
        ),
        27 => array(
            'Id' => 67108864,
            'Label' => 'D4',
        ),
        28 => array(
            'Id' => 134217728,
            'Label' => 'D5',
        ),
        29 => array(
            'Id' => 268435456,
            'Label' => 'D6',
        ),
        30 => array(
            'Id' => 536870912,
            'Label' => 'D7',
        ),
        31 => array(
            'Id' => 1073741824,
            'Label' => 'D8',
        ),
        32 => array(
            'Id' => '2147483648',
            'Label' => 'D9',
        ),
        33 => array(
            'Id' => '4294967296',
            'Label' => 'D10',
        ),
        34 => array(
            'Id' => '8589934592',
            'Label' => 'D11',
        ),
        35 => array(
            'Id' => '17179869184',
            'Label' => 'E1',
        ),
        36 => array(
            'Id' => '34359738368',
            'Label' => 'E2',
        ),
        37 => array(
            'Id' => '68719476736',
            'Label' => 'E3',
        ),
        38 => array(
            'Id' => '137438953472',
            'Label' => 'E4',
        ),
        39 => array(
            'Id' => '274877906944',
            'Label' => 'E5',
        ),
        40 => array(
            'Id' => '549755813888',
            'Label' => 'E6',
        ),
        41 => array(
            'Id' => '1099511627776',
            'Label' => 'E7',
        ),
        42 => array(
            'Id' => '2199023255552',
            'Label' => 'E8',
        ),
        43 => array(
            'Id' => '4398046511104',
            'Label' => 'E9',
        ),
        44 => array(
            'Id' => '8796093022208',
            'Label' => 'E10',
        ),
        45 => array(
            'Id' => '17592186044416',
            'Label' => 'E11',
        ),
        46 => array(
            'Id' => '35184372088832',
            'Label' => 'F1',
        ),
        47 => array(
            'Id' => '70368744177664',
            'Label' => 'F2',
        ),
        48 => array(
            'Id' => '140737488355328',
            'Label' => 'F3',
        ),
        49 => array(
            'Id' => '281474976710656',
            'Label' => 'F4',
        ),
        50 => array(
            'Id' => '562949953421312',
            'Label' => 'F5',
        ),
        51 => array(
            'Id' => '1125899906842624',
            'Label' => 'F6',
        ),
        52 => array(
            'Id' => '2251799813685248',
            'Label' => 'F7',
        ),
        53 => array(
            'Id' => '4503599627370496',
            'Label' => 'F8',
        ),
        54 => array(
            'Id' => '9007199254740992',
            'Label' => 'F9',
        ),
        55 => array(
            'Id' => '18014398509481984',
            'Label' => 'F10',
        ),
        56 => array(
            'Id' => '36028797018963968',
            'Label' => 'F11',
        ),
        57 => array(
            'Id' => '72057594037927936',
            'Label' => 'G1',
        ),
        58 => array(
            'Id' => '144115188075855872',
            'Label' => 'G2',
        ),
        59 => array(
            'Id' => '288230376151711744',
            'Label' => 'G3',
        ),
        60 => array(
            'Id' => '576460752303423488',
            'Label' => 'G4',
        ),
        61 => array(
            'Id' => '1152921504606846976',
            'Label' => 'G5',
        ),
        62 => array(
            'Id' => '2305843009213693952',
            'Label' => 'G6',
        ),
        63 => array(
            'Id' => '4611686018427387904',
            'Label' => 'G7',
        ),
        '9223372036854775808' => array(
            'Id' => '9223372036854775808',
            'Label' => 'G8',
        ),
        64 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        65 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        66 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        67 => array(
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        68 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        69 => array(
            'Id' => 16,
            'Label' => 'Lower-right',
        ),
        70 => array(
            'Id' => 32,
            'Label' => 'Bottom',
        ),
        71 => array(
            'Id' => 64,
            'Label' => 'Lower-left',
        ),
        72 => array(
            'Id' => 128,
            'Label' => 'Left',
        ),
        73 => array(
            'Id' => 256,
            'Label' => 'Upper-left',
        ),
        74 => array(
            'Id' => 512,
            'Label' => 'Far Right',
        ),
        75 => array(
            'Id' => 1024,
            'Label' => 'Far Left',
        ),
        76 => array(
            'Id' => 2048,
            'Label' => 'Upper-middle',
        ),
        77 => array(
            'Id' => 4096,
            'Label' => 'Near Right',
        ),
        78 => array(
            'Id' => 8192,
            'Label' => 'Lower-middle',
        ),
        79 => array(
            'Id' => 16384,
            'Label' => 'Near Left',
        ),
        80 => array(
            'Id' => 32768,
            'Label' => 'Upper Far Right',
        ),
        81 => array(
            'Id' => 65536,
            'Label' => 'Lower Far Right',
        ),
        82 => array(
            'Id' => 131072,
            'Label' => 'Lower Far Left',
        ),
        83 => array(
            'Id' => 262144,
            'Label' => 'Upper Far Left',
        ),
        84 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        85 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        86 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        87 => array(
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        88 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        89 => array(
            'Id' => 16,
            'Label' => 'Lower-right',
        ),
        90 => array(
            'Id' => 32,
            'Label' => 'Bottom',
        ),
        91 => array(
            'Id' => 64,
            'Label' => 'Lower-left',
        ),
        92 => array(
            'Id' => 128,
            'Label' => 'Left',
        ),
        93 => array(
            'Id' => 256,
            'Label' => 'Upper-left',
        ),
        94 => array(
            'Id' => 512,
            'Label' => 'Far Right',
        ),
        95 => array(
            'Id' => 1024,
            'Label' => 'Far Left',
        ),
        96 => array(
            'Id' => 2048,
            'Label' => 'Upper-middle',
        ),
        97 => array(
            'Id' => 4096,
            'Label' => 'Near Right',
        ),
        98 => array(
            'Id' => 8192,
            'Label' => 'Lower-middle',
        ),
        99 => array(
            'Id' => 16384,
            'Label' => 'Near Left',
        ),
        100 => array(
            'Id' => 32768,
            'Label' => 'Upper Far Right',
        ),
        101 => array(
            'Id' => 65536,
            'Label' => 'Lower Far Right',
        ),
        102 => array(
            'Id' => 131072,
            'Label' => 'Lower Far Left',
        ),
        103 => array(
            'Id' => 262144,
            'Label' => 'Upper Far Left',
        ),
        104 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        105 => array(
            'Id' => 1,
            'Label' => 'A5',
        ),
        106 => array(
            'Id' => 2,
            'Label' => 'G10',
        ),
        107 => array(
            'Id' => 4,
            'Label' => 'G11',
        ),
        108 => array(
            'Id' => 8,
            'Label' => 'H2',
        ),
        109 => array(
            'Id' => 16,
            'Label' => 'H3',
        ),
        110 => array(
            'Id' => 32,
            'Label' => 'B4',
        ),
        111 => array(
            'Id' => 64,
            'Label' => 'B5',
        ),
        112 => array(
            'Id' => 128,
            'Label' => 'B6',
        ),
        113 => array(
            'Id' => 256,
            'Label' => 'H7',
        ),
        114 => array(
            'Id' => 512,
            'Label' => 'H8',
        ),
        115 => array(
            'Id' => 1024,
            'Label' => 'B9',
        ),
        116 => array(
            'Id' => 2048,
            'Label' => 'H10',
        ),
        117 => array(
            'Id' => 4096,
            'Label' => 'I5',
        ),
        118 => array(
            'Id' => 8192,
            'Label' => 'C2',
        ),
        119 => array(
            'Id' => 16384,
            'Label' => 'I7',
        ),
        120 => array(
            'Id' => 32768,
            'Label' => 'C4',
        ),
        121 => array(
            'Id' => 65536,
            'Label' => 'C5',
        ),
        122 => array(
            'Id' => 131072,
            'Label' => 'C6',
        ),
        123 => array(
            'Id' => 262144,
            'Label' => 'C7',
        ),
        124 => array(
            'Id' => 524288,
            'Label' => 'C8',
        ),
        125 => array(
            'Id' => 1048576,
            'Label' => 'C9',
        ),
        126 => array(
            'Id' => 2097152,
            'Label' => 'C10',
        ),
        127 => array(
            'Id' => 4194304,
            'Label' => 'C11',
        ),
        128 => array(
            'Id' => 8388608,
            'Label' => 'D1',
        ),
        129 => array(
            'Id' => 16777216,
            'Label' => 'D2',
        ),
        130 => array(
            'Id' => 33554432,
            'Label' => 'D3',
        ),
        131 => array(
            'Id' => 67108864,
            'Label' => 'D4',
        ),
        132 => array(
            'Id' => 134217728,
            'Label' => 'D5',
        ),
        133 => array(
            'Id' => 268435456,
            'Label' => 'D6',
        ),
        134 => array(
            'Id' => 536870912,
            'Label' => 'D7',
        ),
        135 => array(
            'Id' => 1073741824,
            'Label' => 'D8',
        ),
        136 => array(
            'Id' => '2147483648',
            'Label' => 'D9',
        ),
        137 => array(
            'Id' => '4294967296',
            'Label' => 'D10',
        ),
        138 => array(
            'Id' => '8589934592',
            'Label' => 'D11',
        ),
        139 => array(
            'Id' => '17179869184',
            'Label' => 'E1',
        ),
        140 => array(
            'Id' => '34359738368',
            'Label' => 'E2',
        ),
        141 => array(
            'Id' => '68719476736',
            'Label' => 'E3',
        ),
        142 => array(
            'Id' => '137438953472',
            'Label' => 'E4',
        ),
        143 => array(
            'Id' => '274877906944',
            'Label' => 'E5',
        ),
        144 => array(
            'Id' => '549755813888',
            'Label' => 'E6',
        ),
        145 => array(
            'Id' => '1099511627776',
            'Label' => 'E7',
        ),
        146 => array(
            'Id' => '2199023255552',
            'Label' => 'E8',
        ),
        147 => array(
            'Id' => '4398046511104',
            'Label' => 'E9',
        ),
        148 => array(
            'Id' => '8796093022208',
            'Label' => 'E10',
        ),
        149 => array(
            'Id' => '17592186044416',
            'Label' => 'E11',
        ),
        150 => array(
            'Id' => '35184372088832',
            'Label' => 'F1',
        ),
        151 => array(
            'Id' => '70368744177664',
            'Label' => 'F2',
        ),
        152 => array(
            'Id' => '140737488355328',
            'Label' => 'F3',
        ),
        153 => array(
            'Id' => '281474976710656',
            'Label' => 'F4',
        ),
        154 => array(
            'Id' => '562949953421312',
            'Label' => 'F5',
        ),
        155 => array(
            'Id' => '1125899906842624',
            'Label' => 'F6',
        ),
        156 => array(
            'Id' => '2251799813685248',
            'Label' => 'F7',
        ),
        157 => array(
            'Id' => '4503599627370496',
            'Label' => 'F8',
        ),
        158 => array(
            'Id' => '9007199254740992',
            'Label' => 'F9',
        ),
        159 => array(
            'Id' => '18014398509481984',
            'Label' => 'F10',
        ),
        160 => array(
            'Id' => '36028797018963968',
            'Label' => 'F11',
        ),
        161 => array(
            'Id' => '72057594037927936',
            'Label' => 'G1',
        ),
        162 => array(
            'Id' => '144115188075855872',
            'Label' => 'G2',
        ),
        163 => array(
            'Id' => '288230376151711744',
            'Label' => 'G3',
        ),
        164 => array(
            'Id' => '576460752303423488',
            'Label' => 'G4',
        ),
        165 => array(
            'Id' => '1152921504606846976',
            'Label' => 'G5',
        ),
        166 => array(
            'Id' => '2305843009213693952',
            'Label' => 'G6',
        ),
        167 => array(
            'Id' => '4611686018427387904',
            'Label' => 'G7',
        ),
    );

    protected $Index = 'mixed';

}
