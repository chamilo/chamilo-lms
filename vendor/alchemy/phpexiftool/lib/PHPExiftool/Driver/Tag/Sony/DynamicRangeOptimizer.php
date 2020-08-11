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
class DynamicRangeOptimizer extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'DynamicRangeOptimizer';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Dynamic Range Optimizer';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Advanced Auto',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Auto',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'Advanced Lv1',
        ),
        5 => array(
            'Id' => 9,
            'Label' => 'Advanced Lv2',
        ),
        6 => array(
            'Id' => 10,
            'Label' => 'Advanced Lv3',
        ),
        7 => array(
            'Id' => 11,
            'Label' => 'Advanced Lv4',
        ),
        8 => array(
            'Id' => 12,
            'Label' => 'Advanced Lv5',
        ),
        9 => array(
            'Id' => 16,
            'Label' => 'Lv1',
        ),
        10 => array(
            'Id' => 17,
            'Label' => 'Lv2',
        ),
        11 => array(
            'Id' => 18,
            'Label' => 'Lv3',
        ),
        12 => array(
            'Id' => 19,
            'Label' => 'Lv4',
        ),
        13 => array(
            'Id' => 20,
            'Label' => 'Lv5',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Plus',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        18 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        19 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        20 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        21 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        22 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        23 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        24 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        25 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        26 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        27 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        28 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        29 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        30 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        31 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        32 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        33 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        34 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        35 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        36 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        37 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        38 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        39 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        40 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        41 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        42 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        43 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        44 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        45 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        46 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        47 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        48 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        49 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        50 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        51 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        52 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        53 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        54 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        56 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        57 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        58 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        59 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        60 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        61 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        62 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        63 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        64 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        65 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        66 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        67 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        68 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        69 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        70 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        71 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        72 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        73 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        74 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        75 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        76 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        77 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        78 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        79 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        80 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        81 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        82 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        83 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        84 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        85 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        86 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        87 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        88 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        89 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        90 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        91 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        92 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        93 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        94 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        95 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        96 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        97 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        98 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        99 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        100 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        101 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        102 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        103 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        104 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        105 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        106 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        107 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        108 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        109 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        110 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        111 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        112 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        113 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        114 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        115 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        116 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        117 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        118 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        119 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        120 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        121 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        122 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        123 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        124 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        125 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        126 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        127 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        128 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
        129 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        130 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        131 => array(
            'Id' => 3,
            'Label' => 'Lv1',
        ),
        132 => array(
            'Id' => 4,
            'Label' => 'Lv2',
        ),
        133 => array(
            'Id' => 5,
            'Label' => 'Lv3',
        ),
        134 => array(
            'Id' => 6,
            'Label' => 'Lv4',
        ),
        135 => array(
            'Id' => 7,
            'Label' => 'Lv5',
        ),
        136 => array(
            'Id' => 8,
            'Label' => 'n/a',
        ),
    );

}
