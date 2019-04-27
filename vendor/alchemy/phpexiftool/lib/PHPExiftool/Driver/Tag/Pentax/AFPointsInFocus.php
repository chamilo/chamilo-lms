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
class AFPointsInFocus extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointsInFocus';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'AF Points In Focus';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Lower-left, Bottom',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Lower-right, Bottom',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Mid-left, Center',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Center (horizontal)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Mid-right, Center',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Upper-left, Top',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Top',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Upper-right, Top',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Right',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Lower-left, Mid-left',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Upper-left, Mid-left',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Bottom, Center',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Top, Center',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Lower-right, Mid-right',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Upper-right, Mid-right',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Left',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Mid-left',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Center (vertical)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Mid-right',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Fixed Center or Multiple',
        ),
        22 => array(
            'Id' => 1,
            'Label' => 'Top-left',
        ),
        23 => array(
            'Id' => 2,
            'Label' => 'Top-center',
        ),
        24 => array(
            'Id' => 3,
            'Label' => 'Top-right',
        ),
        25 => array(
            'Id' => 4,
            'Label' => 'Left',
        ),
        26 => array(
            'Id' => 5,
            'Label' => 'Center',
        ),
        27 => array(
            'Id' => 6,
            'Label' => 'Right',
        ),
        28 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
        29 => array(
            'Id' => 8,
            'Label' => 'Bottom-center',
        ),
        30 => array(
            'Id' => 9,
            'Label' => 'Bottom-right',
        ),
        31 => array(
            'Id' => 65535,
            'Label' => 'None',
        ),
        32 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        33 => array(
            'Id' => 1,
            'Label' => 'Top-left',
        ),
        34 => array(
            'Id' => 2,
            'Label' => 'Top Near-left',
        ),
        35 => array(
            'Id' => 4,
            'Label' => 'Top',
        ),
        36 => array(
            'Id' => 8,
            'Label' => 'Top Near-right',
        ),
        37 => array(
            'Id' => 16,
            'Label' => 'Top-right',
        ),
        38 => array(
            'Id' => 32,
            'Label' => 'Upper-left',
        ),
        39 => array(
            'Id' => 64,
            'Label' => 'Upper Near-left',
        ),
        40 => array(
            'Id' => 128,
            'Label' => 'Upper-middle',
        ),
        41 => array(
            'Id' => 256,
            'Label' => 'Upper Near-right',
        ),
        42 => array(
            'Id' => 512,
            'Label' => 'Upper-right',
        ),
        43 => array(
            'Id' => 1024,
            'Label' => 'Far Left',
        ),
        44 => array(
            'Id' => 2048,
            'Label' => 'Left',
        ),
        45 => array(
            'Id' => 4096,
            'Label' => 'Near-left',
        ),
        46 => array(
            'Id' => 8192,
            'Label' => 'Center',
        ),
        47 => array(
            'Id' => 16384,
            'Label' => 'Near-right',
        ),
        48 => array(
            'Id' => 32768,
            'Label' => 'Right',
        ),
        49 => array(
            'Id' => 65536,
            'Label' => 'Far Right',
        ),
        50 => array(
            'Id' => 131072,
            'Label' => 'Lower-left',
        ),
        51 => array(
            'Id' => 262144,
            'Label' => 'Lower Near-left',
        ),
        52 => array(
            'Id' => 524288,
            'Label' => 'Lower-middle',
        ),
        53 => array(
            'Id' => 1048576,
            'Label' => 'Lower Near-right',
        ),
        54 => array(
            'Id' => 2097152,
            'Label' => 'Lower-right',
        ),
        55 => array(
            'Id' => 4194304,
            'Label' => 'Bottom-left',
        ),
        56 => array(
            'Id' => 8388608,
            'Label' => 'Bottom Near-left',
        ),
        57 => array(
            'Id' => 16777216,
            'Label' => 'Bottom',
        ),
        58 => array(
            'Id' => 33554432,
            'Label' => 'Bottom Near-right',
        ),
        59 => array(
            'Id' => 67108864,
            'Label' => 'Bottom-right',
        ),
        60 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        61 => array(
            'Id' => 1,
            'Label' => 'Upper-left',
        ),
        62 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        63 => array(
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        64 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        65 => array(
            'Id' => 16,
            'Label' => 'Mid-left',
        ),
        66 => array(
            'Id' => 32,
            'Label' => 'Center',
        ),
        67 => array(
            'Id' => 64,
            'Label' => 'Mid-right',
        ),
        68 => array(
            'Id' => 128,
            'Label' => 'Right',
        ),
        69 => array(
            'Id' => 256,
            'Label' => 'Lower-left',
        ),
        70 => array(
            'Id' => 512,
            'Label' => 'Bottom',
        ),
        71 => array(
            'Id' => 1024,
            'Label' => 'Lower-right',
        ),
    );

    protected $Index = 'mixed';

}
