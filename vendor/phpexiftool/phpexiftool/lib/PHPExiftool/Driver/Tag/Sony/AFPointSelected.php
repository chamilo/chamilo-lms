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
class AFPointSelected extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointSelected';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point Selected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        16 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        17 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        18 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        19 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        20 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        21 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        25 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        26 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        27 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        28 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        29 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        30 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        31 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        33 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        34 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        35 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        36 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        37 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        38 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        39 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        40 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        41 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        42 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        43 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        44 => array(
            'Id' => 12,
            'Label' => 'Upper-middle',
        ),
        45 => array(
            'Id' => 13,
            'Label' => 'Near Right',
        ),
        46 => array(
            'Id' => 14,
            'Label' => 'Lower-middle',
        ),
        47 => array(
            'Id' => 15,
            'Label' => 'Near Left',
        ),
        48 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        49 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        50 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        51 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        52 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        53 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        54 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        56 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        57 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        58 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        59 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        60 => array(
            'Id' => 12,
            'Label' => 'Upper-middle',
        ),
        61 => array(
            'Id' => 13,
            'Label' => 'Near Right',
        ),
        62 => array(
            'Id' => 14,
            'Label' => 'Lower-middle',
        ),
        63 => array(
            'Id' => 15,
            'Label' => 'Near Left',
        ),
        64 => array(
            'Id' => 16,
            'Label' => 'Upper Far Right',
        ),
        65 => array(
            'Id' => 17,
            'Label' => 'Lower Far Right',
        ),
        66 => array(
            'Id' => 18,
            'Label' => 'Lower Far Left',
        ),
        67 => array(
            'Id' => 19,
            'Label' => 'Upper Far Left',
        ),
        68 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        69 => array(
            'Id' => 40,
            'Label' => 'Center',
        ),
    );

    protected $Index = 'mixed';

}
