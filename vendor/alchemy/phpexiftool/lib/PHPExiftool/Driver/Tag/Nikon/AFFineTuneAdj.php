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
class AFFineTuneAdj extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFFineTuneAdj';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AF Fine Tune Adj';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        1 => array(
            'Id' => 58,
            'Label' => '+1',
        ),
        2 => array(
            'Id' => 59,
            'Label' => '+2',
        ),
        3 => array(
            'Id' => 60,
            'Label' => '+4',
        ),
        4 => array(
            'Id' => 61,
            'Label' => '+8',
        ),
        5 => array(
            'Id' => 62,
            'Label' => '+16',
        ),
        6 => array(
            'Id' => 194,
            'Label' => '-16',
        ),
        7 => array(
            'Id' => 195,
            'Label' => '-8',
        ),
        8 => array(
            'Id' => 196,
            'Label' => '-4',
        ),
        9 => array(
            'Id' => 197,
            'Label' => '-2',
        ),
        10 => array(
            'Id' => 198,
            'Label' => '-1',
        ),
        11 => array(
            'Id' => 4158,
            'Label' => '+17',
        ),
        12 => array(
            'Id' => 4290,
            'Label' => '-17',
        ),
        13 => array(
            'Id' => 8253,
            'Label' => '+9',
        ),
        14 => array(
            'Id' => 8254,
            'Label' => '+18',
        ),
        15 => array(
            'Id' => 8386,
            'Label' => '-18',
        ),
        16 => array(
            'Id' => 8387,
            'Label' => '-9',
        ),
        17 => array(
            'Id' => 12350,
            'Label' => '+19',
        ),
        18 => array(
            'Id' => 12482,
            'Label' => '-19',
        ),
        19 => array(
            'Id' => 16444,
            'Label' => '+5',
        ),
        20 => array(
            'Id' => 16445,
            'Label' => '+10',
        ),
        21 => array(
            'Id' => 16446,
            'Label' => '+20',
        ),
        22 => array(
            'Id' => 16578,
            'Label' => '-20',
        ),
        23 => array(
            'Id' => 16579,
            'Label' => '-10',
        ),
        24 => array(
            'Id' => 16580,
            'Label' => '-5',
        ),
        25 => array(
            'Id' => 24637,
            'Label' => '+11',
        ),
        26 => array(
            'Id' => 24771,
            'Label' => '-11',
        ),
        27 => array(
            'Id' => 32827,
            'Label' => '+3',
        ),
        28 => array(
            'Id' => 32828,
            'Label' => '+6',
        ),
        29 => array(
            'Id' => 32829,
            'Label' => '+12',
        ),
        30 => array(
            'Id' => 32963,
            'Label' => '-12',
        ),
        31 => array(
            'Id' => 32964,
            'Label' => '-6',
        ),
        32 => array(
            'Id' => 32965,
            'Label' => '-3',
        ),
        33 => array(
            'Id' => 41021,
            'Label' => '+13',
        ),
        34 => array(
            'Id' => 41155,
            'Label' => '-13',
        ),
        35 => array(
            'Id' => 49212,
            'Label' => '+7',
        ),
        36 => array(
            'Id' => 49213,
            'Label' => '+14',
        ),
        37 => array(
            'Id' => 49347,
            'Label' => '-14',
        ),
        38 => array(
            'Id' => 49348,
            'Label' => '-7',
        ),
        39 => array(
            'Id' => 57405,
            'Label' => '+15',
        ),
        40 => array(
            'Id' => 57539,
            'Label' => '-15',
        ),
        41 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        42 => array(
            'Id' => 2110,
            'Label' => '+11',
        ),
        43 => array(
            'Id' => 2242,
            'Label' => '-11',
        ),
        44 => array(
            'Id' => 8252,
            'Label' => '+3',
        ),
        45 => array(
            'Id' => 8253,
            'Label' => '+6',
        ),
        46 => array(
            'Id' => 8254,
            'Label' => '+12',
        ),
        47 => array(
            'Id' => 8386,
            'Label' => '-12',
        ),
        48 => array(
            'Id' => 8387,
            'Label' => '-6',
        ),
        49 => array(
            'Id' => 8388,
            'Label' => '-3',
        ),
        50 => array(
            'Id' => 14398,
            'Label' => '+13',
        ),
        51 => array(
            'Id' => 14530,
            'Label' => '-13',
        ),
        52 => array(
            'Id' => 20541,
            'Label' => '+7',
        ),
        53 => array(
            'Id' => 20542,
            'Label' => '+14',
        ),
        54 => array(
            'Id' => 20674,
            'Label' => '-14',
        ),
        55 => array(
            'Id' => 20675,
            'Label' => '-7',
        ),
        56 => array(
            'Id' => 26686,
            'Label' => '+15',
        ),
        57 => array(
            'Id' => 26818,
            'Label' => '-15',
        ),
        58 => array(
            'Id' => 32826,
            'Label' => '+1',
        ),
        59 => array(
            'Id' => 32827,
            'Label' => '+2',
        ),
        60 => array(
            'Id' => 32828,
            'Label' => '+4',
        ),
        61 => array(
            'Id' => 32829,
            'Label' => '+8',
        ),
        62 => array(
            'Id' => 32830,
            'Label' => '+16',
        ),
        63 => array(
            'Id' => 32962,
            'Label' => '-16',
        ),
        64 => array(
            'Id' => 32963,
            'Label' => '-8',
        ),
        65 => array(
            'Id' => 32964,
            'Label' => '-4',
        ),
        66 => array(
            'Id' => 32965,
            'Label' => '-2',
        ),
        67 => array(
            'Id' => 32966,
            'Label' => '-1',
        ),
        68 => array(
            'Id' => 38974,
            'Label' => '+17',
        ),
        69 => array(
            'Id' => 39106,
            'Label' => '-17',
        ),
        70 => array(
            'Id' => 45117,
            'Label' => '+9',
        ),
        71 => array(
            'Id' => 45118,
            'Label' => '+18',
        ),
        72 => array(
            'Id' => 45250,
            'Label' => '-18',
        ),
        73 => array(
            'Id' => 45251,
            'Label' => '-9',
        ),
        74 => array(
            'Id' => 51262,
            'Label' => '+19',
        ),
        75 => array(
            'Id' => 51394,
            'Label' => '-19',
        ),
        76 => array(
            'Id' => 57404,
            'Label' => '+5',
        ),
        77 => array(
            'Id' => 57405,
            'Label' => '+10',
        ),
        78 => array(
            'Id' => 57406,
            'Label' => '+20',
        ),
        79 => array(
            'Id' => 57538,
            'Label' => '-20',
        ),
        80 => array(
            'Id' => 57539,
            'Label' => '-10',
        ),
        81 => array(
            'Id' => 57540,
            'Label' => '-5',
        ),
        82 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        83 => array(
            'Id' => 1086,
            'Label' => '+13',
        ),
        84 => array(
            'Id' => 1218,
            'Label' => '-13',
        ),
        85 => array(
            'Id' => 6205,
            'Label' => '+7',
        ),
        86 => array(
            'Id' => 6206,
            'Label' => '+14',
        ),
        87 => array(
            'Id' => 6338,
            'Label' => '-14',
        ),
        88 => array(
            'Id' => 6339,
            'Label' => '-7',
        ),
        89 => array(
            'Id' => 11326,
            'Label' => '+15',
        ),
        90 => array(
            'Id' => 11458,
            'Label' => '-15',
        ),
        91 => array(
            'Id' => 16442,
            'Label' => '+1',
        ),
        92 => array(
            'Id' => 16443,
            'Label' => '+2',
        ),
        93 => array(
            'Id' => 16444,
            'Label' => '+4',
        ),
        94 => array(
            'Id' => 16445,
            'Label' => '+8',
        ),
        95 => array(
            'Id' => 16446,
            'Label' => '+16',
        ),
        96 => array(
            'Id' => 16578,
            'Label' => '-16',
        ),
        97 => array(
            'Id' => 16579,
            'Label' => '-8',
        ),
        98 => array(
            'Id' => 16580,
            'Label' => '-4',
        ),
        99 => array(
            'Id' => 16581,
            'Label' => '-2',
        ),
        100 => array(
            'Id' => 16582,
            'Label' => '-1',
        ),
        101 => array(
            'Id' => 21566,
            'Label' => '+17',
        ),
        102 => array(
            'Id' => 21698,
            'Label' => '-17',
        ),
        103 => array(
            'Id' => 26685,
            'Label' => '+9',
        ),
        104 => array(
            'Id' => 26686,
            'Label' => '+18',
        ),
        105 => array(
            'Id' => 26818,
            'Label' => '-18',
        ),
        106 => array(
            'Id' => 26819,
            'Label' => '-9',
        ),
        107 => array(
            'Id' => 31806,
            'Label' => '+19',
        ),
        108 => array(
            'Id' => 31938,
            'Label' => '-19',
        ),
        109 => array(
            'Id' => 36924,
            'Label' => '+5',
        ),
        110 => array(
            'Id' => 36925,
            'Label' => '+10',
        ),
        111 => array(
            'Id' => 36926,
            'Label' => '+20',
        ),
        112 => array(
            'Id' => 37058,
            'Label' => '-20',
        ),
        113 => array(
            'Id' => 37059,
            'Label' => '-10',
        ),
        114 => array(
            'Id' => 37060,
            'Label' => '-5',
        ),
        115 => array(
            'Id' => 47165,
            'Label' => '+11',
        ),
        116 => array(
            'Id' => 47299,
            'Label' => '-11',
        ),
        117 => array(
            'Id' => 57403,
            'Label' => '+3',
        ),
        118 => array(
            'Id' => 57404,
            'Label' => '+6',
        ),
        119 => array(
            'Id' => 57405,
            'Label' => '+12',
        ),
        120 => array(
            'Id' => 57539,
            'Label' => '-12',
        ),
        121 => array(
            'Id' => 57540,
            'Label' => '-6',
        ),
        122 => array(
            'Id' => 57541,
            'Label' => '-3',
        ),
    );

    protected $Index = 'mixed';

}
