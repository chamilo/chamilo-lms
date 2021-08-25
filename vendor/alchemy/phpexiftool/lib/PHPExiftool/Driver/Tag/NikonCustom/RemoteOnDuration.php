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
class RemoteOnDuration extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'RemoteOnDuration';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Remote On Duration';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        1 => array(
            'Id' => 64,
            'Label' => '5 min',
        ),
        2 => array(
            'Id' => 128,
            'Label' => '10 min',
        ),
        3 => array(
            'Id' => 192,
            'Label' => '15 min',
        ),
        4 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        5 => array(
            'Id' => 1,
            'Label' => '5 min',
        ),
        6 => array(
            'Id' => 2,
            'Label' => '10 min',
        ),
        7 => array(
            'Id' => 3,
            'Label' => '15 min',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        9 => array(
            'Id' => 1,
            'Label' => '5 min',
        ),
        10 => array(
            'Id' => 2,
            'Label' => '10 min',
        ),
        11 => array(
            'Id' => 3,
            'Label' => '20 min',
        ),
        12 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        13 => array(
            'Id' => 1,
            'Label' => '5 min',
        ),
        14 => array(
            'Id' => 2,
            'Label' => '10 min',
        ),
        15 => array(
            'Id' => 3,
            'Label' => '15 min',
        ),
        16 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        17 => array(
            'Id' => 1,
            'Label' => '5 min',
        ),
        18 => array(
            'Id' => 2,
            'Label' => '10 min',
        ),
        19 => array(
            'Id' => 3,
            'Label' => '15 min',
        ),
        20 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        21 => array(
            'Id' => 64,
            'Label' => '5 min',
        ),
        22 => array(
            'Id' => 128,
            'Label' => '10 min',
        ),
        23 => array(
            'Id' => 192,
            'Label' => '15 min',
        ),
        24 => array(
            'Id' => 0,
            'Label' => '1 min',
        ),
        25 => array(
            'Id' => 1,
            'Label' => '5 min',
        ),
        26 => array(
            'Id' => 2,
            'Label' => '10 min',
        ),
        27 => array(
            'Id' => 3,
            'Label' => '15 min',
        ),
    );

}
