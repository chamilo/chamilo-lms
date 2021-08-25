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
class AELockButtonPlusDials extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AELockButtonPlusDials';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AE Lock Button Plus Dials';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area (FX/DX/5:4)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'One Step Speed/Aperture',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Choose Non-CPU Lens Number',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Choose Image Area (FX/DX)',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Shooting Bank Menu',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'Dynamic AF Area',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Choose Non-CPU Lens Number',
        ),
        9 => array(
            'Id' => 5,
            'Label' => 'Auto Bracketing',
        ),
        10 => array(
            'Id' => 6,
            'Label' => 'Dynamic AF Area',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        12 => array(
            'Id' => 16,
            'Label' => 'Choose Image Area',
        ),
        13 => array(
            'Id' => 32,
            'Label' => 'One Step Speed/Aperture',
        ),
        14 => array(
            'Id' => 48,
            'Label' => 'Choose Non-CPU Lens Number',
        ),
        15 => array(
            'Id' => 80,
            'Label' => 'Auto bracketing',
        ),
        16 => array(
            'Id' => 96,
            'Label' => 'Dynamic AF Area',
        ),
        17 => array(
            'Id' => 112,
            'Label' => 'Shutter speed & Aperture lock',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area (FX/DX/5:4)',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        21 => array(
            'Id' => 4,
            'Label' => 'Choose Non-CPU Lens Number',
        ),
        22 => array(
            'Id' => 8,
            'Label' => 'Exposure Delay Mode',
        ),
    );

    protected $Index = 'mixed';

}
