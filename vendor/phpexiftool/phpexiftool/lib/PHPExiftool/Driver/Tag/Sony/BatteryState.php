<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BatteryState extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'BatteryState';

    protected $FullName = 'Sony::ExtraInfo3';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Battery State';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Empty',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Low',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Half full',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Almost full',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Full',
        ),
    );

}
