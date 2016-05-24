<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalanceAdj extends AbstractTag
{

    protected $Id = 24;

    protected $Name = 'WhiteBalanceAdj';

    protected $FullName = 'CanonVRD::Ver1';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'White Balance Adj';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Tungsten',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Fluorescent',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Flash',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Shade',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Kelvin',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Manual (Click)',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Shot Settings',
        ),
    );

}
