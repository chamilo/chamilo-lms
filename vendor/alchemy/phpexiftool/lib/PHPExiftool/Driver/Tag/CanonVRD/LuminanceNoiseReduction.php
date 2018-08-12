<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LuminanceNoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LuminanceNoiseReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Luminance Noise Reduction';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 65,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 100,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        4 => array(
            'Id' => 65,
            'Label' => 1,
        ),
        5 => array(
            'Id' => 100,
            'Label' => 2,
        ),
        6 => array(
            'Id' => 110,
            'Label' => 3,
        ),
        7 => array(
            'Id' => 120,
            'Label' => 4,
        ),
        8 => array(
            'Id' => 130,
            'Label' => 5,
        ),
        9 => array(
            'Id' => 140,
            'Label' => 6,
        ),
        10 => array(
            'Id' => 150,
            'Label' => 7,
        ),
        11 => array(
            'Id' => 160,
            'Label' => 8,
        ),
        12 => array(
            'Id' => 170,
            'Label' => 9,
        ),
        13 => array(
            'Id' => 180,
            'Label' => 10,
        ),
        14 => array(
            'Id' => 181,
            'Label' => 11,
        ),
        15 => array(
            'Id' => 182,
            'Label' => 12,
        ),
        16 => array(
            'Id' => 183,
            'Label' => 13,
        ),
        17 => array(
            'Id' => 184,
            'Label' => 14,
        ),
        18 => array(
            'Id' => 185,
            'Label' => 15,
        ),
        19 => array(
            'Id' => 186,
            'Label' => 16,
        ),
        20 => array(
            'Id' => 187,
            'Label' => 17,
        ),
        21 => array(
            'Id' => 188,
            'Label' => 18,
        ),
        22 => array(
            'Id' => 189,
            'Label' => 19,
        ),
        23 => array(
            'Id' => 190,
            'Label' => 20,
        ),
    );

    protected $Index = 'mixed';

}
