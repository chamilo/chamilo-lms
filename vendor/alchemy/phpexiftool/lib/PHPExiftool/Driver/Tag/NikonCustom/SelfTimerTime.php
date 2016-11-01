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
class SelfTimerTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SelfTimerTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Self Timer Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        1 => array(
            'Id' => 8,
            'Label' => '5 s',
        ),
        2 => array(
            'Id' => 16,
            'Label' => '10 s',
        ),
        3 => array(
            'Id' => 24,
            'Label' => '20 s',
        ),
        4 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        5 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        6 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        7 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        9 => array(
            'Id' => 1,
            'Label' => '5 s',
        ),
        10 => array(
            'Id' => 2,
            'Label' => '10 s',
        ),
        11 => array(
            'Id' => 3,
            'Label' => '20 s',
        ),
        12 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        13 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        14 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        15 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
        16 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        17 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        18 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        19 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
        20 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        21 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        22 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        23 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
        24 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        25 => array(
            'Id' => 16,
            'Label' => '5 s',
        ),
        26 => array(
            'Id' => 32,
            'Label' => '10 s',
        ),
        27 => array(
            'Id' => 48,
            'Label' => '20 s',
        ),
        28 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        29 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        30 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        31 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
        32 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        33 => array(
            'Id' => 1,
            'Label' => '5 s',
        ),
        34 => array(
            'Id' => 2,
            'Label' => '10 s',
        ),
        35 => array(
            'Id' => 3,
            'Label' => '20 s',
        ),
        36 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        37 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        38 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        39 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
        40 => array(
            'Id' => 0,
            'Label' => '2 s',
        ),
        41 => array(
            'Id' => 64,
            'Label' => '5 s',
        ),
        42 => array(
            'Id' => 128,
            'Label' => '10 s',
        ),
        43 => array(
            'Id' => 192,
            'Label' => '20 s',
        ),
    );

}
