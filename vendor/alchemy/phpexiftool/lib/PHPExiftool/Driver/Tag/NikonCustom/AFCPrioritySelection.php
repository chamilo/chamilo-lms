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
class AFCPrioritySelection extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AF-CPrioritySelection';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF-C Priority Selection';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'Release + Focus',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        4 => array(
            'Id' => 64,
            'Label' => 'Release + Focus',
        ),
        5 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
        6 => array(
            'Id' => 192,
            'Label' => 'Focus + Release',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        10 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        12 => array(
            'Id' => 64,
            'Label' => 'Release + Focus',
        ),
        13 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        15 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        17 => array(
            'Id' => 64,
            'Label' => 'Release + Focus',
        ),
        18 => array(
            'Id' => 128,
            'Label' => 'Focus',
        ),
    );

}
