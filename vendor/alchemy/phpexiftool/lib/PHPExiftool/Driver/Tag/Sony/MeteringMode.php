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
class MeteringMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MeteringMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

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
        9 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        14 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        17 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        19 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        22 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        23 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        25 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        26 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        27 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        28 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        29 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        30 => array(
            'Id' => 0,
            'Label' => 'Multi-segment',
        ),
        31 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        32 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
    );

}
