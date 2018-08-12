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
class FocusTrackingLockOn extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusTrackingLockOn';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Focus Tracking Lock On';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Long',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Short',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 0,
            'Label' => '5 (Long)',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 4,
        ),
        6 => array(
            'Id' => 2,
            'Label' => '3 (Normal)',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 2,
        ),
        8 => array(
            'Id' => 4,
            'Label' => '1 (Short)',
        ),
        9 => array(
            'Id' => 5,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 1,
            'Label' => '1 (Short)',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 2,
        ),
        13 => array(
            'Id' => 3,
            'Label' => '3 (Normal)',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 4,
        ),
        15 => array(
            'Id' => 5,
            'Label' => '5 (Long)',
        ),
        16 => array(
            'Id' => 0,
            'Label' => '3 Normal',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 4,
        ),
        18 => array(
            'Id' => 2,
            'Label' => '5 Long',
        ),
        19 => array(
            'Id' => 3,
            'Label' => 2,
        ),
        20 => array(
            'Id' => 4,
            'Label' => '1 Short',
        ),
        21 => array(
            'Id' => 5,
            'Label' => 'Off',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        23 => array(
            'Id' => 1,
            'Label' => '1 Short',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 2,
        ),
        25 => array(
            'Id' => 3,
            'Label' => '3 Normal',
        ),
        26 => array(
            'Id' => 4,
            'Label' => 4,
        ),
        27 => array(
            'Id' => 5,
            'Label' => '5 Long',
        ),
        28 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        29 => array(
            'Id' => 1,
            'Label' => '1 (Short)',
        ),
        30 => array(
            'Id' => 2,
            'Label' => 2,
        ),
        31 => array(
            'Id' => 3,
            'Label' => '3 (Normal)',
        ),
        32 => array(
            'Id' => 4,
            'Label' => 4,
        ),
        33 => array(
            'Id' => 5,
            'Label' => '5 (Long)',
        ),
    );

}
