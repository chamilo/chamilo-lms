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
class FocusMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'AF-A',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'AF-S',
        ),
        8 => array(
            'Id' => 6,
            'Label' => 'DMF',
        ),
        9 => array(
            'Id' => 7,
            'Label' => 'AF-D',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'Permanent-AF',
        ),
        13 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        17 => array(
            'Id' => 5,
            'Label' => 'Semi-manual',
        ),
        18 => array(
            'Id' => 6,
            'Label' => 'Direct Manual Focus',
        ),
        19 => array(
            'Id' => 17,
            'Label' => 'AF-S',
        ),
        20 => array(
            'Id' => 18,
            'Label' => 'AF-C',
        ),
        21 => array(
            'Id' => 19,
            'Label' => 'AF-A',
        ),
        22 => array(
            'Id' => 32,
            'Label' => 'Manual',
        ),
    );

}
