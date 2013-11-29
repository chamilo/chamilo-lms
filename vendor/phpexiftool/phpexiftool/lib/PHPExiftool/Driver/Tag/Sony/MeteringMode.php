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
class MeteringMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MeteringMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Metering Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Multi-segment',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Center-weighted Average',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'Spot',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Multi-segment',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        5 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Multi-segment',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
    );

}
