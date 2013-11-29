<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
            'Label' => 'Center',
        ),
    );

    protected $Index = 'mixed';

}
