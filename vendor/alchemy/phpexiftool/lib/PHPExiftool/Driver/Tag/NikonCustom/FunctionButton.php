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
class FunctionButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FunctionButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Function Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        1 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        2 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        3 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'AF-ON',
        ),
        5 => array(
            'Id' => 16,
            'Label' => '+ NEF (RAW)',
        ),
        6 => array(
            'Id' => 18,
            'Label' => 'Active D-Lighting',
        ),
        7 => array(
            'Id' => 25,
            'Label' => 'Live View',
        ),
        8 => array(
            'Id' => 26,
            'Label' => 'Image Quality',
        ),
        9 => array(
            'Id' => 27,
            'Label' => 'ISO',
        ),
        10 => array(
            'Id' => 28,
            'Label' => 'White Balance',
        ),
        11 => array(
            'Id' => 29,
            'Label' => 'HDR',
        ),
        12 => array(
            'Id' => 30,
            'Label' => 'Auto Bracketing',
        ),
        13 => array(
            'Id' => 31,
            'Label' => 'AF-area Mode',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'ISO Display',
        ),
        15 => array(
            'Id' => 8,
            'Label' => 'Framing Grid',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'AF-area Mode',
        ),
        17 => array(
            'Id' => 24,
            'Label' => 'Center AF Area',
        ),
        18 => array(
            'Id' => 32,
            'Label' => 'FV Lock',
        ),
        19 => array(
            'Id' => 40,
            'Label' => 'Flash Off',
        ),
        20 => array(
            'Id' => 48,
            'Label' => 'Matrix Metering',
        ),
        21 => array(
            'Id' => 56,
            'Label' => 'Center-weighted',
        ),
        22 => array(
            'Id' => 64,
            'Label' => 'Spot Metering',
        ),
    );

}
