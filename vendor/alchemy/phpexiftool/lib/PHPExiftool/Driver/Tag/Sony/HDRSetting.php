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
class HDRSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'HDRSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'HDR Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'On (Auto)',
        ),
        2 => array(
            'Id' => 17,
            'Label' => 'On (Manual)',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        5 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        6 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        8 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        9 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        10 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        14 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        15 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        16 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        17 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        18 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        19 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        20 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        21 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        22 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        23 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        24 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        25 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        26 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        27 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        28 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        29 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        30 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        31 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        32 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        33 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        34 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        35 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        36 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        37 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        38 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        39 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        40 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        41 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        42 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        43 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        44 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        45 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        46 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        47 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        48 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        49 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        50 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        51 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        52 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        53 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        54 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        56 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        57 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        58 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
        59 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        60 => array(
            'Id' => 1,
            'Label' => 'HDR Auto',
        ),
        61 => array(
            'Id' => 3,
            'Label' => 'HDR 1 EV',
        ),
        62 => array(
            'Id' => 5,
            'Label' => 'HDR 2 EV',
        ),
        63 => array(
            'Id' => 7,
            'Label' => 'HDR 3 EV',
        ),
        64 => array(
            'Id' => 9,
            'Label' => 'HDR 4 EV',
        ),
        65 => array(
            'Id' => 11,
            'Label' => 'HDR 5 EV',
        ),
        66 => array(
            'Id' => 13,
            'Label' => 'HDR 6 EV',
        ),
    );

}
