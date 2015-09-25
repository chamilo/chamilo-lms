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
class LiveViewMonitorOffTime extends AbstractTag
{

    protected $Id = '20.2';

    protected $Name = 'LiveViewMonitorOffTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Live View Monitor Off Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '3 min',
        ),
        1 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        2 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        3 => array(
            'Id' => 12,
            'Label' => '15 min',
        ),
        4 => array(
            'Id' => 16,
            'Label' => '20 min',
        ),
        5 => array(
            'Id' => 20,
            'Label' => '30 min',
        ),
        6 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        7 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        8 => array(
            'Id' => 12,
            'Label' => '15 min',
        ),
        9 => array(
            'Id' => 16,
            'Label' => '20 min',
        ),
        10 => array(
            'Id' => 20,
            'Label' => '30 min',
        ),
        11 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        12 => array(
            'Id' => 4,
            'Label' => '10 s',
        ),
        13 => array(
            'Id' => 8,
            'Label' => '20 s',
        ),
        14 => array(
            'Id' => 12,
            'Label' => '1 min',
        ),
        15 => array(
            'Id' => 16,
            'Label' => '5 min',
        ),
        16 => array(
            'Id' => 20,
            'Label' => '10 min',
        ),
    );

}
