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
class MenuMonitorOffTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MenuMonitorOffTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Menu Monitor Off Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        1 => array(
            'Id' => 32,
            'Label' => '10 s',
        ),
        2 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        3 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        4 => array(
            'Id' => 128,
            'Label' => '5 min',
        ),
        5 => array(
            'Id' => 160,
            'Label' => '10 min',
        ),
        6 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        7 => array(
            'Id' => 1,
            'Label' => '10 s',
        ),
        8 => array(
            'Id' => 2,
            'Label' => '20 s',
        ),
        9 => array(
            'Id' => 3,
            'Label' => '1 min',
        ),
        10 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        11 => array(
            'Id' => 5,
            'Label' => '10 min',
        ),
        12 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        13 => array(
            'Id' => 32,
            'Label' => '10 s',
        ),
        14 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        15 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        16 => array(
            'Id' => 128,
            'Label' => '5 min',
        ),
        17 => array(
            'Id' => 160,
            'Label' => '10 min',
        ),
        18 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        19 => array(
            'Id' => 64,
            'Label' => '10 s',
        ),
        20 => array(
            'Id' => 128,
            'Label' => '20 s',
        ),
        21 => array(
            'Id' => 160,
            'Label' => '1 min',
        ),
        22 => array(
            'Id' => 192,
            'Label' => '5 min',
        ),
        23 => array(
            'Id' => 224,
            'Label' => '10 min',
        ),
        24 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        25 => array(
            'Id' => 32,
            'Label' => '10 s',
        ),
        26 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        27 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        28 => array(
            'Id' => 128,
            'Label' => '5 min',
        ),
        29 => array(
            'Id' => 160,
            'Label' => '10 min',
        ),
    );

}
