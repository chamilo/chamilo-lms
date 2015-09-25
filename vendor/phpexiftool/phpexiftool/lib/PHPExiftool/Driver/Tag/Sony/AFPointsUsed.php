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
class AFPointsUsed extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'AFPointsUsed';

    protected $FullName = 'Sony::AFInfo2';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AF Points Used';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
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
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        5 => array(
            'Id' => 16,
            'Label' => 'Lower-right',
        ),
        6 => array(
            'Id' => 32,
            'Label' => 'Bottom',
        ),
        7 => array(
            'Id' => 64,
            'Label' => 'Lower-left',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'Left',
        ),
        9 => array(
            'Id' => 256,
            'Label' => 'Upper-left',
        ),
        10 => array(
            'Id' => 512,
            'Label' => 'Far Right',
        ),
        11 => array(
            'Id' => 1024,
            'Label' => 'Far Left',
        ),
        12 => array(
            'Id' => 2048,
            'Label' => 'Upper-middle',
        ),
        13 => array(
            'Id' => 4096,
            'Label' => 'Near Right',
        ),
        14 => array(
            'Id' => 8192,
            'Label' => 'Lower-middle',
        ),
        15 => array(
            'Id' => 16384,
            'Label' => 'Near Left',
        ),
        16 => array(
            'Id' => 32768,
            'Label' => 'Upper Far Right',
        ),
        17 => array(
            'Id' => 65536,
            'Label' => 'Lower Far Right',
        ),
        18 => array(
            'Id' => 131072,
            'Label' => 'Lower Far Left',
        ),
        19 => array(
            'Id' => 262144,
            'Label' => 'Upper Far Left',
        ),
        20 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        21 => array(
            'Id' => 549755813888,
            'Label' => 'Center',
        ),
    );

    protected $MaxLength = 'mixed';

    protected $Index = 'mixed';

}
