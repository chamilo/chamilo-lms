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
class CommandDialsChangeMainSub extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CommandDialsChangeMainSub';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Command Dials Change Main Sub';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        3 => array(
            'Id' => 32,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        4 => array(
            'Id' => 64,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        5 => array(
            'Id' => 128,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        6 => array(
            'Id' => 160,
            'Label' => 'Autofocus On, Exposure On',
        ),
        7 => array(
            'Id' => 192,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 64,
            'Label' => 'On',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 32,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 64,
            'Label' => 'On (A mode only)',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        14 => array(
            'Id' => 32,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        15 => array(
            'Id' => 64,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        16 => array(
            'Id' => 128,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        17 => array(
            'Id' => 160,
            'Label' => 'Autofocus On, Exposure On',
        ),
        18 => array(
            'Id' => 192,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
        ),
    );

}
