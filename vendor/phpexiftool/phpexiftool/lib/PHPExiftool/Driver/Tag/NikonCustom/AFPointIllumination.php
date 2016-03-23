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
class AFPointIllumination extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointIllumination';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point Illumination';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'On in Continuous Shooting and Manual Focusing',
        ),
        1 => array(
            'Id' => 32,
            'Label' => 'On During Manual Focusing',
        ),
        2 => array(
            'Id' => 64,
            'Label' => 'On in Continuous Shooting Modes',
        ),
        3 => array(
            'Id' => 96,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 4,
            'Label' => 'On',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'On',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        15 => array(
            'Id' => 4,
            'Label' => 'Off',
        ),
    );

    protected $Index = 'mixed';

}
