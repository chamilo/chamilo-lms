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
class AFPointAtShutterRelease extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'AFPointAtShutterRelease';

    protected $FullName = 'Sony::AFInfo';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point At Shutter Release';

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
        18 => array(
            'Id' => 30,
            'Label' => '(out of focus)',
        ),
        19 => array(
            'Id' => 0,
            'Label' => 'Upper Far Left',
        ),
        20 => array(
            'Id' => 1,
            'Label' => 'Upper-left (horizontal)',
        ),
        21 => array(
            'Id' => 2,
            'Label' => 'Far Left (horizontal)',
        ),
        22 => array(
            'Id' => 3,
            'Label' => 'Left (horizontal)',
        ),
        23 => array(
            'Id' => 4,
            'Label' => 'Lower Far Left',
        ),
        24 => array(
            'Id' => 5,
            'Label' => 'Lower-left (horizontal)',
        ),
        25 => array(
            'Id' => 6,
            'Label' => 'Upper-left (vertical)',
        ),
        26 => array(
            'Id' => 7,
            'Label' => 'Left (vertical)',
        ),
        27 => array(
            'Id' => 8,
            'Label' => 'Lower-left (vertical)',
        ),
        28 => array(
            'Id' => 9,
            'Label' => 'Far Left (vertical)',
        ),
        29 => array(
            'Id' => 10,
            'Label' => 'Top (horizontal)',
        ),
        30 => array(
            'Id' => 11,
            'Label' => 'Near Right',
        ),
        31 => array(
            'Id' => 12,
            'Label' => 'Center (horizontal)',
        ),
        32 => array(
            'Id' => 13,
            'Label' => 'Near Left',
        ),
        33 => array(
            'Id' => 14,
            'Label' => 'Bottom (horizontal)',
        ),
        34 => array(
            'Id' => 15,
            'Label' => 'Top (vertical)',
        ),
        35 => array(
            'Id' => 16,
            'Label' => 'Upper-middle',
        ),
        36 => array(
            'Id' => 17,
            'Label' => 'Center (vertical)',
        ),
        37 => array(
            'Id' => 18,
            'Label' => 'Lower-middle',
        ),
        38 => array(
            'Id' => 19,
            'Label' => 'Bottom (vertical)',
        ),
        39 => array(
            'Id' => 20,
            'Label' => 'Upper Far Right',
        ),
        40 => array(
            'Id' => 21,
            'Label' => 'Upper-right (horizontal)',
        ),
        41 => array(
            'Id' => 22,
            'Label' => 'Far Right (horizontal)',
        ),
        42 => array(
            'Id' => 23,
            'Label' => 'Right (horizontal)',
        ),
        43 => array(
            'Id' => 24,
            'Label' => 'Lower Far Right',
        ),
        44 => array(
            'Id' => 25,
            'Label' => 'Lower-right (horizontal)',
        ),
        45 => array(
            'Id' => 26,
            'Label' => 'Far Right (vertical)',
        ),
        46 => array(
            'Id' => 27,
            'Label' => 'Upper-right (vertical)',
        ),
        47 => array(
            'Id' => 28,
            'Label' => 'Right (vertical)',
        ),
        48 => array(
            'Id' => 29,
            'Label' => 'Lower-right (vertical)',
        ),
        49 => array(
            'Id' => 30,
            'Label' => '(out of focus)',
        ),
    );

    protected $Index = 'mixed';

}
