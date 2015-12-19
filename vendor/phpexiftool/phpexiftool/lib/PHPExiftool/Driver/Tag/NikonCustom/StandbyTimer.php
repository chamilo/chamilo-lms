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
class StandbyTimer extends AbstractTag
{

    protected $Id = '18.1';

    protected $Name = 'StandbyTimer';

    protected $FullName = 'NikonCustom::SettingsD5200';

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
        32 => array(
            'Id' => 32,
            'Label' => '8 s',
        ),
        64 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        96 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        128 => array(
            'Id' => 128,
            'Label' => '30 min',
        ),
    );

}
