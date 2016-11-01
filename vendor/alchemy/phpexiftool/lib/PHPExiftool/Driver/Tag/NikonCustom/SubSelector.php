<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class SubSelector extends AbstractTag
{

    protected $Id = '49.1';

    protected $Name = 'SubSelector';

    protected $FullName = 'NikonCustom::SettingsD4';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Sub Selector';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Preview',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'FV Lock',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'AE/AF Lock',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'AE Lock (reset on release)',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'AE Lock (hold)',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'AF Lock Only',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'AF-On',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Bracketing Burst',
        ),
        88 => array(
            'Id' => 88,
            'Label' => 'Matrix Metering',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Center-weighted Metering',
        ),
        104 => array(
            'Id' => 104,
            'Label' => 'Spot Metering',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'Playback',
        ),
        120 => array(
            'Id' => 120,
            'Label' => 'My Menu Top Item',
        ),
        128 => array(
            'Id' => 128,
            'Label' => '+NEF(RAW)',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Virtual Horizon',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'My Menu',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'Reset',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Grid Display',
        ),
        168 => array(
            'Id' => 168,
            'Label' => 'Disable Synchronized Release',
        ),
        176 => array(
            'Id' => 176,
            'Label' => 'Remote Release Only',
        ),
        184 => array(
            'Id' => 184,
            'Label' => 'Preview',
        ),
        208 => array(
            'Id' => 208,
            'Label' => 'Flash Disable/Enable',
        ),
    );

}
