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
class FuncButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FuncButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Func Button';

    protected $flag_Permanent = true;

    protected $Index = 1;

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
            'Id' => 72,
            'Label' => 'Flash Off',
        ),
        9 => array(
            'Id' => 80,
            'Label' => 'Bracketing Burst',
        ),
        10 => array(
            'Id' => 88,
            'Label' => 'Matrix Metering',
        ),
        11 => array(
            'Id' => 96,
            'Label' => 'Center-weighted Metering',
        ),
        12 => array(
            'Id' => 104,
            'Label' => 'Spot Metering',
        ),
        13 => array(
            'Id' => 112,
            'Label' => 'Playback',
        ),
        14 => array(
            'Id' => 120,
            'Label' => 'My Menu Top',
        ),
        15 => array(
            'Id' => 128,
            'Label' => '+ NEF (RAW)',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        17 => array(
            'Id' => 8,
            'Label' => 'Preview',
        ),
        18 => array(
            'Id' => 16,
            'Label' => 'FV Lock',
        ),
        19 => array(
            'Id' => 24,
            'Label' => 'AE/AF Lock',
        ),
        20 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        21 => array(
            'Id' => 40,
            'Label' => 'AE Lock (reset on release)',
        ),
        22 => array(
            'Id' => 48,
            'Label' => 'AE Lock (hold)',
        ),
        23 => array(
            'Id' => 56,
            'Label' => 'AF Lock Only',
        ),
        24 => array(
            'Id' => 64,
            'Label' => 'Flash Off',
        ),
        25 => array(
            'Id' => 72,
            'Label' => 'Bracketing Burst',
        ),
        26 => array(
            'Id' => 80,
            'Label' => 'Matrix Metering',
        ),
        27 => array(
            'Id' => 88,
            'Label' => 'Center-weighted Metering',
        ),
        28 => array(
            'Id' => 96,
            'Label' => 'Spot Metering',
        ),
        29 => array(
            'Id' => 104,
            'Label' => 'Virtual Horizon',
        ),
        30 => array(
            'Id' => 120,
            'Label' => 'Playback',
        ),
        31 => array(
            'Id' => 128,
            'Label' => 'My Menu Top',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        33 => array(
            'Id' => 8,
            'Label' => 'Preview',
        ),
        34 => array(
            'Id' => 16,
            'Label' => 'FV Lock',
        ),
        35 => array(
            'Id' => 24,
            'Label' => 'AE/AF Lock',
        ),
        36 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        37 => array(
            'Id' => 40,
            'Label' => 'AE Lock (reset on release)',
        ),
        38 => array(
            'Id' => 48,
            'Label' => 'AE Lock (hold)',
        ),
        39 => array(
            'Id' => 56,
            'Label' => 'AF Lock Only',
        ),
        40 => array(
            'Id' => 72,
            'Label' => 'Flash Off',
        ),
        41 => array(
            'Id' => 80,
            'Label' => 'Bracketing Burst',
        ),
        42 => array(
            'Id' => 88,
            'Label' => 'Matrix Metering',
        ),
        43 => array(
            'Id' => 96,
            'Label' => 'Center-weighted Metering',
        ),
        44 => array(
            'Id' => 104,
            'Label' => 'Spot Metering',
        ),
        45 => array(
            'Id' => 112,
            'Label' => 'My Menu Top',
        ),
        46 => array(
            'Id' => 120,
            'Label' => 'Live View',
        ),
        47 => array(
            'Id' => 128,
            'Label' => '+ NEF (RAW)',
        ),
        48 => array(
            'Id' => 136,
            'Label' => 'Virtual Horizon',
        ),
        49 => array(
            'Id' => 0,
            'Label' => 'Grid Display',
        ),
        50 => array(
            'Id' => 8,
            'Label' => 'FV Lock',
        ),
        51 => array(
            'Id' => 16,
            'Label' => 'Flash Off',
        ),
        52 => array(
            'Id' => 24,
            'Label' => 'Matrix Metering',
        ),
        53 => array(
            'Id' => 32,
            'Label' => 'Center-weighted Metering',
        ),
        54 => array(
            'Id' => 40,
            'Label' => 'Spot Metering',
        ),
        55 => array(
            'Id' => 48,
            'Label' => 'My Menu Top',
        ),
        56 => array(
            'Id' => 56,
            'Label' => '+ NEF (RAW)',
        ),
        57 => array(
            'Id' => 64,
            'Label' => 'Active D-Lighting',
        ),
        58 => array(
            'Id' => 72,
            'Label' => 'Preview',
        ),
        59 => array(
            'Id' => 80,
            'Label' => 'AE/AF Lock',
        ),
        60 => array(
            'Id' => 88,
            'Label' => 'AE Lock Only',
        ),
        61 => array(
            'Id' => 96,
            'Label' => 'AF Lock Only',
        ),
        62 => array(
            'Id' => 104,
            'Label' => 'AE Lock (hold)',
        ),
        63 => array(
            'Id' => 112,
            'Label' => 'Bracketing Burst',
        ),
        64 => array(
            'Id' => 120,
            'Label' => 'Playback',
        ),
        65 => array(
            'Id' => 128,
            'Label' => '1EV Step Speed/Aperture',
        ),
        66 => array(
            'Id' => 136,
            'Label' => 'Choose Non-CPU Lens',
        ),
        67 => array(
            'Id' => 144,
            'Label' => 'Virtual Horizon',
        ),
        68 => array(
            'Id' => 152,
            'Label' => 'Start Movie Recording',
        ),
        69 => array(
            'Id' => 8,
            'Label' => 'Framing Grid',
        ),
        70 => array(
            'Id' => 16,
            'Label' => 'AF-area Mode',
        ),
        71 => array(
            'Id' => 24,
            'Label' => 'Center Focus Point',
        ),
        72 => array(
            'Id' => 32,
            'Label' => 'FV Lock',
        ),
        73 => array(
            'Id' => 40,
            'Label' => 'Flash Off',
        ),
        74 => array(
            'Id' => 48,
            'Label' => 'Matrix Metering',
        ),
        75 => array(
            'Id' => 56,
            'Label' => 'Center-weighted Metering',
        ),
        76 => array(
            'Id' => 64,
            'Label' => 'Spot Metering',
        ),
        77 => array(
            'Id' => 72,
            'Label' => 'My Menu Top',
        ),
        78 => array(
            'Id' => 80,
            'Label' => '+ NEF (RAW)',
        ),
    );

}
