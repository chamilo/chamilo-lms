<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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

    protected $g2 = 'mixed';

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
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'AF-A',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'AF-A',
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
            'Id' => 0,
            'Label' => 'Manual',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        17 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        21 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        22 => array(
            'Id' => 4,
            'Label' => 'DMF',
        ),
        23 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        24 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        25 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        26 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        27 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        28 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        29 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        30 => array(
            'Id' => 4,
            'Label' => 'AF-A',
        ),
        31 => array(
            'Id' => 6,
            'Label' => 'DMF',
        ),
        32 => array(
            'Id' => 7,
            'Label' => 'AF-D',
        ),
        33 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        34 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        35 => array(
            'Id' => 4,
            'Label' => 'Permanent-AF',
        ),
        36 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
        37 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        38 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        39 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        40 => array(
            'Id' => 5,
            'Label' => 'Semi-manual',
        ),
        41 => array(
            'Id' => 6,
            'Label' => 'DMF',
        ),
        42 => array(
            'Id' => 17,
            'Label' => 'AF-S',
        ),
        43 => array(
            'Id' => 18,
            'Label' => 'AF-C',
        ),
        44 => array(
            'Id' => 19,
            'Label' => 'AF-A',
        ),
        45 => array(
            'Id' => 32,
            'Label' => 'Manual',
        ),
        46 => array(
            'Id' => 48,
            'Label' => 'DMF',
        ),
        47 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        48 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        49 => array(
            'Id' => 3,
            'Label' => 'AF-C',
        ),
        50 => array(
            'Id' => 4,
            'Label' => 'AF-A',
        ),
        51 => array(
            'Id' => 6,
            'Label' => 'DMF',
        ),
    );

}
