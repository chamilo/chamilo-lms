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
class ShootingInfoDisplay extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ShootingInfoDisplay';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Shooting Info Display';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 32,
            'Label' => 'Manual (dark on light)',
        ),
        3 => array(
            'Id' => 48,
            'Label' => 'Manual (light on dark)',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 128,
            'Label' => 'Manual (dark on light)',
        ),
        6 => array(
            'Id' => 192,
            'Label' => 'Manual (light on dark)',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'Manual (dark on light)',
        ),
        9 => array(
            'Id' => 192,
            'Label' => 'Manual (light on dark)',
        ),
    );

}
