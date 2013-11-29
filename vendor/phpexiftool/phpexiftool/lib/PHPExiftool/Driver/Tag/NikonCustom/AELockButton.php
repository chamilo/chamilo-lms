<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AELockButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AELockButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AE Lock Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 8,
            'Label' => 'Preview',
        ),
        2 => array(
            'Id' => 16,
            'Label' => 'FV Lock',
        ),
        3 => array(
            'Id' => 24,
            'Label' => 'AE/AF Lock',
        ),
        4 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        5 => array(
            'Id' => 40,
            'Label' => 'AE Lock (reset on release)',
        ),
        6 => array(
            'Id' => 48,
            'Label' => 'AE Lock (hold)',
        ),
        7 => array(
            'Id' => 56,
            'Label' => 'AF Lock Only',
        ),
        8 => array(
            'Id' => 64,
            'Label' => 'Flash Off',
        ),
        9 => array(
            'Id' => 72,
            'Label' => 'Bracketing Burst',
        ),
        10 => array(
            'Id' => 80,
            'Label' => 'Matrix Metering',
        ),
        11 => array(
            'Id' => 88,
            'Label' => 'Center-weighted Metering',
        ),
        12 => array(
            'Id' => 96,
            'Label' => 'Spot Metering',
        ),
        13 => array(
            'Id' => 104,
            'Label' => 'Virtual Horizon',
        ),
        14 => array(
            'Id' => 112,
            'Label' => 'AF On',
        ),
        15 => array(
            'Id' => 120,
            'Label' => 'Playback',
        ),
        16 => array(
            'Id' => 128,
            'Label' => 'My Menu Top',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        18 => array(
            'Id' => 8,
            'Label' => 'Preview',
        ),
        19 => array(
            'Id' => 16,
            'Label' => 'FV Lock',
        ),
        20 => array(
            'Id' => 24,
            'Label' => 'AE/AF Lock',
        ),
        21 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        22 => array(
            'Id' => 40,
            'Label' => 'AE Lock (reset on release)',
        ),
        23 => array(
            'Id' => 48,
            'Label' => 'AE Lock (hold)',
        ),
        24 => array(
            'Id' => 56,
            'Label' => 'AF Lock Only',
        ),
        25 => array(
            'Id' => 64,
            'Label' => 'AF On',
        ),
        26 => array(
            'Id' => 72,
            'Label' => 'Flash Off',
        ),
        27 => array(
            'Id' => 80,
            'Label' => 'Bracketing Burst',
        ),
        28 => array(
            'Id' => 88,
            'Label' => 'Matrix Metering',
        ),
        29 => array(
            'Id' => 96,
            'Label' => 'Center-weighted Metering',
        ),
        30 => array(
            'Id' => 104,
            'Label' => 'Spot Metering',
        ),
        31 => array(
            'Id' => 112,
            'Label' => 'Playback',
        ),
        32 => array(
            'Id' => 120,
            'Label' => 'My Menu Top',
        ),
        33 => array(
            'Id' => 128,
            'Label' => '+ NEF (RAW)',
        ),
        34 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        35 => array(
            'Id' => 2,
            'Label' => 'AE Lock Only',
        ),
        36 => array(
            'Id' => 4,
            'Label' => 'AF Lock Only',
        ),
        37 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        38 => array(
            'Id' => 8,
            'Label' => 'AF-ON',
        ),
        39 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        40 => array(
            'Id' => 8,
            'Label' => 'AE Lock Only',
        ),
        41 => array(
            'Id' => 16,
            'Label' => 'AF Lock Only',
        ),
        42 => array(
            'Id' => 24,
            'Label' => 'AE Lock (hold)',
        ),
        43 => array(
            'Id' => 32,
            'Label' => 'AF-ON',
        ),
        44 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        45 => array(
            'Id' => 8,
            'Label' => 'Preview',
        ),
        46 => array(
            'Id' => 16,
            'Label' => 'FV Lock',
        ),
        47 => array(
            'Id' => 24,
            'Label' => 'AE/AF Lock',
        ),
        48 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        49 => array(
            'Id' => 40,
            'Label' => 'AE Lock (reset on release)',
        ),
        50 => array(
            'Id' => 48,
            'Label' => 'AE Lock (hold)',
        ),
        51 => array(
            'Id' => 56,
            'Label' => 'AF Lock Only',
        ),
        52 => array(
            'Id' => 64,
            'Label' => 'AF-ON',
        ),
        53 => array(
            'Id' => 72,
            'Label' => 'Flash Off',
        ),
        54 => array(
            'Id' => 80,
            'Label' => 'Bracketing Burst',
        ),
        55 => array(
            'Id' => 88,
            'Label' => 'Matrix Metering',
        ),
        56 => array(
            'Id' => 96,
            'Label' => 'Center-weighted Metering',
        ),
        57 => array(
            'Id' => 104,
            'Label' => 'Spot Metering',
        ),
        58 => array(
            'Id' => 112,
            'Label' => 'My Menu Top',
        ),
        59 => array(
            'Id' => 120,
            'Label' => 'Live View',
        ),
        60 => array(
            'Id' => 128,
            'Label' => '+ NEF (RAW)',
        ),
        61 => array(
            'Id' => 136,
            'Label' => 'Virtual Horizon',
        ),
        62 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        63 => array(
            'Id' => 8,
            'Label' => 'AE Lock Only',
        ),
        64 => array(
            'Id' => 16,
            'Label' => 'AF Lock Only',
        ),
        65 => array(
            'Id' => 24,
            'Label' => 'AE Lock (hold)',
        ),
        66 => array(
            'Id' => 32,
            'Label' => 'AF-ON',
        ),
        67 => array(
            'Id' => 40,
            'Label' => 'FV Lock',
        ),
        68 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        69 => array(
            'Id' => 2,
            'Label' => 'AE Lock Only',
        ),
        70 => array(
            'Id' => 4,
            'Label' => 'AF Lock Only',
        ),
        71 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        72 => array(
            'Id' => 8,
            'Label' => 'AF-ON',
        ),
        73 => array(
            'Id' => 10,
            'Label' => 'FV Lock',
        ),
        74 => array(
            'Id' => 12,
            'Label' => 'Focus Area Selection',
        ),
        75 => array(
            'Id' => 14,
            'Label' => 'AE-L/AF-L/AF Area',
        ),
        76 => array(
            'Id' => 16,
            'Label' => 'AE-L/AF Area',
        ),
        77 => array(
            'Id' => 18,
            'Label' => 'AF-L/AF Area',
        ),
        78 => array(
            'Id' => 20,
            'Label' => 'AF-ON/AF Area',
        ),
        79 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        80 => array(
            'Id' => 8,
            'Label' => 'AE Lock Only',
        ),
        81 => array(
            'Id' => 16,
            'Label' => 'AF Lock Only',
        ),
        82 => array(
            'Id' => 24,
            'Label' => 'AE Lock (hold)',
        ),
        83 => array(
            'Id' => 32,
            'Label' => 'AF-ON',
        ),
        84 => array(
            'Id' => 40,
            'Label' => 'FV Lock',
        ),
    );

    protected $Index = 'mixed';

}
