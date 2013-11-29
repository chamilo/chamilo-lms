<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPointsInFocus extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointsInFocus';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'AF Points In Focus';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Lower-left, Bottom',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Lower-right, Bottom',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Mid-left, Center',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Center (horizontal)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Mid-right, Center',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Upper-left, Top',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Top',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Upper-right, Top',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Right',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Lower-left, Mid-left',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Upper-left, Mid-left',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Bottom, Center',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Top, Center',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Lower-right, Mid-right',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Upper-right, Mid-right',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Left',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Mid-left',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Center (vertical)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Mid-right',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Fixed Center or Multiple',
        ),
        22 => array(
            'Id' => 1,
            'Label' => 'Top-left',
        ),
        23 => array(
            'Id' => 2,
            'Label' => 'Top-center',
        ),
        24 => array(
            'Id' => 3,
            'Label' => 'Top-right',
        ),
        25 => array(
            'Id' => 4,
            'Label' => 'Left',
        ),
        26 => array(
            'Id' => 5,
            'Label' => 'Center',
        ),
        27 => array(
            'Id' => 6,
            'Label' => 'Right',
        ),
        28 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
        29 => array(
            'Id' => 8,
            'Label' => 'Bottom-center',
        ),
        30 => array(
            'Id' => 9,
            'Label' => 'Bottom-right',
        ),
        31 => array(
            'Id' => 65535,
            'Label' => 'None',
        ),
        32 => array(
            'Id' => 1,
            'Label' => 'Upper-left',
        ),
        33 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        34 => array(
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        35 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        36 => array(
            'Id' => 16,
            'Label' => 'Mid-left',
        ),
        37 => array(
            'Id' => 32,
            'Label' => 'Center',
        ),
        38 => array(
            'Id' => 64,
            'Label' => 'Mid-right',
        ),
        39 => array(
            'Id' => 128,
            'Label' => 'Right',
        ),
        40 => array(
            'Id' => 256,
            'Label' => 'Lower-left',
        ),
        41 => array(
            'Id' => 512,
            'Label' => 'Bottom',
        ),
        42 => array(
            'Id' => 1024,
            'Label' => 'Lower-right',
        ),
    );

}
