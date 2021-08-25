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
class LensFocusFunctionButtons extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensFocusFunctionButtons';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Lens Focus Function Buttons';

    protected $flag_Permanent = true;

    protected $Values = array(
        3 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Preset focus Point',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'AF-Area Mode:  Single-point AF',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'AF-Area Mode: Dynamic-area AF (9 points)',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'AF-Area Mode: Dynamic-area AF (21 points)',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'AF-Area Mode: Dynamic-area AF (51 points)',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode: Group-area AF',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode: Auto area AF',
        ),
    );

}
