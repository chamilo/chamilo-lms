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
class AFPointSelected extends AbstractTag
{

    protected $Id = 14;

    protected $Name = 'AFPointSelected';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point Selected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Upper-left',
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
            'Label' => 'Left',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Mid-left',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Center',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Mid-right',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Lower-left',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Bottom',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Lower-right',
        ),
        12 => array(
            'Id' => 65531,
            'Label' => 'AF Select',
        ),
        13 => array(
            'Id' => 65532,
            'Label' => 'Face Detect AF',
        ),
        14 => array(
            'Id' => 65533,
            'Label' => 'Automatic Tracking AF',
        ),
        15 => array(
            'Id' => 65534,
            'Label' => 'Fixed Center',
        ),
        16 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        18 => array(
            'Id' => 1,
            'Label' => 'Top-left',
        ),
        19 => array(
            'Id' => 2,
            'Label' => 'Top Near-left',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Top',
        ),
        21 => array(
            'Id' => 4,
            'Label' => 'Top Near-right',
        ),
        22 => array(
            'Id' => 5,
            'Label' => 'Top-right',
        ),
        23 => array(
            'Id' => 6,
            'Label' => 'Upper-left',
        ),
        24 => array(
            'Id' => 7,
            'Label' => 'Upper Near-left',
        ),
        25 => array(
            'Id' => 8,
            'Label' => 'Upper-middle',
        ),
        26 => array(
            'Id' => 9,
            'Label' => 'Upper Near-right',
        ),
        27 => array(
            'Id' => 10,
            'Label' => 'Upper-right',
        ),
        28 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        29 => array(
            'Id' => 12,
            'Label' => 'Left',
        ),
        30 => array(
            'Id' => 13,
            'Label' => 'Near-left',
        ),
        31 => array(
            'Id' => 14,
            'Label' => 'Center',
        ),
        32 => array(
            'Id' => 15,
            'Label' => 'Near-right',
        ),
        33 => array(
            'Id' => 16,
            'Label' => 'Right',
        ),
        34 => array(
            'Id' => 17,
            'Label' => 'Far Right',
        ),
        35 => array(
            'Id' => 18,
            'Label' => 'Lower-left',
        ),
        36 => array(
            'Id' => 19,
            'Label' => 'Lower Near-left',
        ),
        37 => array(
            'Id' => 20,
            'Label' => 'Lower-middle',
        ),
        38 => array(
            'Id' => 21,
            'Label' => 'Lower Near-right',
        ),
        39 => array(
            'Id' => 22,
            'Label' => 'Lower-right',
        ),
        40 => array(
            'Id' => 23,
            'Label' => 'Bottom-left',
        ),
        41 => array(
            'Id' => 24,
            'Label' => 'Bottom Near-left',
        ),
        42 => array(
            'Id' => 25,
            'Label' => 'Bottom',
        ),
        43 => array(
            'Id' => 26,
            'Label' => 'Bottom Near-right',
        ),
        44 => array(
            'Id' => 27,
            'Label' => 'Bottom-right',
        ),
        45 => array(
            'Id' => 257,
            'Label' => 'Zone Select Top-left',
        ),
        46 => array(
            'Id' => 258,
            'Label' => 'Zone Select Top Near-left',
        ),
        47 => array(
            'Id' => 259,
            'Label' => 'Zone Select Top',
        ),
        48 => array(
            'Id' => 260,
            'Label' => 'Zone Select Top Near-right',
        ),
        49 => array(
            'Id' => 261,
            'Label' => 'Zone Select Top-right',
        ),
        50 => array(
            'Id' => 262,
            'Label' => 'Zone Select Upper-left',
        ),
        51 => array(
            'Id' => 263,
            'Label' => 'Zone Select Upper Near-left',
        ),
        52 => array(
            'Id' => 264,
            'Label' => 'Zone Select Upper-middle',
        ),
        53 => array(
            'Id' => 265,
            'Label' => 'Zone Select Upper Near-right',
        ),
        54 => array(
            'Id' => 266,
            'Label' => 'Zone Select Upper-right',
        ),
        55 => array(
            'Id' => 267,
            'Label' => 'Zone Select Far Left',
        ),
        56 => array(
            'Id' => 268,
            'Label' => 'Zone Select Left',
        ),
        57 => array(
            'Id' => 269,
            'Label' => 'Zone Select Near-left',
        ),
        58 => array(
            'Id' => 270,
            'Label' => 'Zone Select Center',
        ),
        59 => array(
            'Id' => 271,
            'Label' => 'Zone Select Near-right',
        ),
        60 => array(
            'Id' => 272,
            'Label' => 'Zone Select Right',
        ),
        61 => array(
            'Id' => 273,
            'Label' => 'Zone Select Far Right',
        ),
        62 => array(
            'Id' => 274,
            'Label' => 'Zone Select Lower-left',
        ),
        63 => array(
            'Id' => 275,
            'Label' => 'Zone Select Lower Near-left',
        ),
        64 => array(
            'Id' => 276,
            'Label' => 'Zone Select Lower-middle',
        ),
        65 => array(
            'Id' => 277,
            'Label' => 'Zone Select Lower Near-right',
        ),
        66 => array(
            'Id' => 278,
            'Label' => 'Zone Select Lower-right',
        ),
        67 => array(
            'Id' => 279,
            'Label' => 'Zone Select Bottom-left',
        ),
        68 => array(
            'Id' => 280,
            'Label' => 'Zone Select Bottom Near-left',
        ),
        69 => array(
            'Id' => 281,
            'Label' => 'Zone Select Bottom',
        ),
        70 => array(
            'Id' => 282,
            'Label' => 'Zone Select Bottom Near-right',
        ),
        71 => array(
            'Id' => 283,
            'Label' => 'Zone Select Bottom-right',
        ),
        72 => array(
            'Id' => 65531,
            'Label' => 'AF Select',
        ),
        73 => array(
            'Id' => 65532,
            'Label' => 'Face Detect AF',
        ),
        74 => array(
            'Id' => 65533,
            'Label' => 'Automatic Tracking AF',
        ),
        75 => array(
            'Id' => 65534,
            'Label' => 'Fixed Center',
        ),
        76 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
    );

    protected $Index = 'mixed';

}
