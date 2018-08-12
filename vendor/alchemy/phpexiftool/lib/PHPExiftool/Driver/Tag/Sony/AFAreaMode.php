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
class AFAreaMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFAreaMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AF Area Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Wide',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Spot',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Local',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Zone',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Wide',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Flexible Spot',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'Zone',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'Expanded Flexible Spot',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Wide',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Local',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Wide',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Local',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Wide',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
        17 => array(
            'Id' => 3,
            'Label' => 'Local',
        ),
        18 => array(
            'Id' => 4,
            'Label' => 'Flexible',
        ),
        19 => array(
            'Id' => 0,
            'Label' => 'Default',
        ),
        20 => array(
            'Id' => 1,
            'Label' => 'Multi',
        ),
        21 => array(
            'Id' => 2,
            'Label' => 'Center',
        ),
        22 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        23 => array(
            'Id' => 4,
            'Label' => 'Flexible Spot',
        ),
        24 => array(
            'Id' => 6,
            'Label' => 'Touch',
        ),
        25 => array(
            'Id' => 14,
            'Label' => 'Tracking',
        ),
        26 => array(
            'Id' => 15,
            'Label' => 'Face Tracking',
        ),
        27 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
        28 => array(
            'Id' => 0,
            'Label' => 'Multi',
        ),
        29 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        30 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
        31 => array(
            'Id' => 3,
            'Label' => 'Flexible Spot',
        ),
        32 => array(
            'Id' => 10,
            'Label' => 'Selective (for Miniature effect)',
        ),
        33 => array(
            'Id' => 14,
            'Label' => 'Tracking',
        ),
        34 => array(
            'Id' => 15,
            'Label' => 'Face Tracking',
        ),
        35 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
        36 => array(
            'Id' => 0,
            'Label' => 'Multi',
        ),
        37 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        38 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
        39 => array(
            'Id' => 3,
            'Label' => 'Flexible Spot',
        ),
        40 => array(
            'Id' => 10,
            'Label' => 'Selective (for Miniature effect)',
        ),
        41 => array(
            'Id' => 11,
            'Label' => 'Zone',
        ),
        42 => array(
            'Id' => 12,
            'Label' => 'Expanded Flexible Spot',
        ),
        43 => array(
            'Id' => 14,
            'Label' => 'Tracking',
        ),
        44 => array(
            'Id' => 15,
            'Label' => 'Face Tracking',
        ),
        45 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
    );

    protected $Index = 'mixed';

}
