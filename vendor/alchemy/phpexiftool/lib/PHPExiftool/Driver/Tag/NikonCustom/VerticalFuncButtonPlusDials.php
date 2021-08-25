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
class VerticalFuncButtonPlusDials extends AbstractTag
{

    protected $Id = '43.1';

    protected $Name = 'VerticalFuncButtonPlusDials';

    protected $FullName = 'NikonCustom::SettingsD4';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Vertical Func Button Plus Dials';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Choose Image Area (FX/DX/5:4)',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'One Step Speed / Aperture',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Choose Non-CPU Lens Number',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Active D-Lighting',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Shooting Bank Menu',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'ISO Sensitivity',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Exposure Mode',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'Exposure Compensation',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Metering',
        ),
    );

}
