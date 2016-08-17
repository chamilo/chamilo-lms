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
class Beep extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Beep';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Beep';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'High',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Medium',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'High',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        11 => array(
            'Id' => 128,
            'Label' => 'High',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        14 => array(
            'Id' => 128,
            'Label' => 'High',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        16 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        17 => array(
            'Id' => 128,
            'Label' => 'High',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'High',
        ),
        19 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        20 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        22 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        23 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        24 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        25 => array(
            'Id' => 2,
            'Label' => 'Medium',
        ),
        26 => array(
            'Id' => 3,
            'Label' => 'High',
        ),
        27 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        28 => array(
            'Id' => 64,
            'Label' => 'On',
        ),
    );

}
