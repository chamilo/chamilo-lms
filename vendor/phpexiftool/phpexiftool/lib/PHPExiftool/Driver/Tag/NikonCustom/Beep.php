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
            'Label' => 'On',
        ),
        4 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        7 => array(
            'Id' => 128,
            'Label' => 'High',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'High',
        ),
        9 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        10 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        14 => array(
            'Id' => 64,
            'Label' => 'On',
        ),
    );

}
