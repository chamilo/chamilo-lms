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
class AFMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AF Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Default',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Multi',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Center',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Flexible Spot',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Touch',
        ),
        6 => array(
            'Id' => 14,
            'Label' => 'Manual Focus',
        ),
        7 => array(
            'Id' => 15,
            'Label' => 'Face Detected',
        ),
        8 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Multi',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'Flexible Spot',
        ),
        12 => array(
            'Id' => 14,
            'Label' => 'Tracking',
        ),
        13 => array(
            'Id' => 15,
            'Label' => 'Smile Shutter',
        ),
        14 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
    );

}
