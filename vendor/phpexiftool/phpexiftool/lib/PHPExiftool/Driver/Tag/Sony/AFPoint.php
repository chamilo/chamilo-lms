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
class AFPoint extends AbstractTag
{

    protected $Id = 32;

    protected $Name = 'AFPoint';

    protected $FullName = 'Sony::CameraInfo';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Upper-left',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Lower-left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Far Left',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Top (horizontal)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Near Right',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Center (horizontal)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Near Left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Bottom (horizontal)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Top (vertical)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Center (vertical)',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Bottom (vertical)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Far Right',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Upper-right',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Right',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Lower-right',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Upper-middle',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Lower-middle',
        ),
        255 => array(
            'Id' => 255,
            'Label' => '(none)',
        ),
    );

}
