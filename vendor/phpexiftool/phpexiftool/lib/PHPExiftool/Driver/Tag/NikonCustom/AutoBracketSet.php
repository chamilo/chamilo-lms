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
            'Label' => 'Exposure',
        ),
        5 => array(
            'Id' => 64,
            'Label' => 'Active D-Lighting',
        ),
        6 => array(
            'Id' => 128,
            'Label' => 'WB Bracketing',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Exposure',
        ),
        8 => array(
            'Id' => 64,
            'Label' => 'WB Bracketing',
        ),
        9 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Exposure',
        ),
        11 => array(
            'Id' => 64,
            'Label' => 'WB Bracketing',
        ),
        12 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        14 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        15 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        16 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        18 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        19 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        20 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        21 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        23 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        24 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        25 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        27 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        28 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        29 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        30 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
    );

}
