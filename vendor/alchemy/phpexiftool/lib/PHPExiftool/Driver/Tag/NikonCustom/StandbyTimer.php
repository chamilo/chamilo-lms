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
class StandbyTimer extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'StandbyTimer';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Standby Timer';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        1 => array(
            'Id' => 16,
            'Label' => '6 s',
        ),
        2 => array(
            'Id' => 48,
            'Label' => '10 s',
        ),
        3 => array(
            'Id' => 80,
            'Label' => '30 s',
        ),
        4 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        5 => array(
            'Id' => 112,
            'Label' => '5 min',
        ),
        6 => array(
            'Id' => 128,
            'Label' => '10 min',
        ),
        7 => array(
            'Id' => 144,
            'Label' => '30 min',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        9 => array(
            'Id' => 32,
            'Label' => '8 s',
        ),
        10 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        11 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        12 => array(
            'Id' => 128,
            'Label' => '30 min',
        ),
        13 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        14 => array(
            'Id' => 16,
            'Label' => '6 s',
        ),
        15 => array(
            'Id' => 48,
            'Label' => '10 s',
        ),
        16 => array(
            'Id' => 80,
            'Label' => '30 s',
        ),
        17 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        18 => array(
            'Id' => 112,
            'Label' => '5 min',
        ),
        19 => array(
            'Id' => 128,
            'Label' => '10 min',
        ),
        20 => array(
            'Id' => 144,
            'Label' => '30 min',
        ),
    );

}
