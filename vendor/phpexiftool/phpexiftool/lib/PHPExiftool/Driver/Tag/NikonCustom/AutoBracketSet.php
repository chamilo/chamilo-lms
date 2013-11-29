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
            'Label' => 'AE & Flash',
        ),
        8 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        9 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        10 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        12 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        13 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        14 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        15 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        17 => array(
            'Id' => 64,
            'Label' => 'AE Only',
        ),
        18 => array(
            'Id' => 128,
            'Label' => 'Flash Only',
        ),
        19 => array(
            'Id' => 192,
            'Label' => 'WB Bracketing',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'AE & Flash',
        ),
        21 => array(
            'Id' => 32,
            'Label' => 'AE Only',
        ),
        22 => array(
            'Id' => 64,
            'Label' => 'Flash Only',
        ),
        23 => array(
            'Id' => 96,
            'Label' => 'WB Bracketing',
        ),
        24 => array(
            'Id' => 128,
            'Label' => 'Active D-Lighting',
        ),
    );

}
