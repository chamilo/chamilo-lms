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
class AutoBracketSet extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AutoBracketSet';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Auto Bracket Set';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        3 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        5 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        6 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        7 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'ADL Bracketing',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        10 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        11 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        12 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        13 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Exposure',
        ),
        15 => array(
            'Id' => 64,
            'Label' => 'Active D-Lighting',
        ),
        16 => array(
            'Id' => 128,
            'Label' => 'WB Bracketing',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'Exposure',
        ),
        18 => array(
            'Id' => 64,
            'Label' => 'WB Bracketing',
        ),
        19 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'Exposure',
        ),
        21 => array(
            'Id' => 64,
            'Label' => 'WB Bracketing',
        ),
        22 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        23 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        24 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        25 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        26 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        27 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        28 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        29 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        30 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        31 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        33 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        34 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        35 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        36 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        37 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        38 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        39 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        40 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        41 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        42 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        43 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        44 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        45 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
    );

    protected $Index = 'mixed';

}
