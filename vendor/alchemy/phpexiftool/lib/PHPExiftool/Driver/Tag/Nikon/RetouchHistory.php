<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RetouchHistory extends AbstractTag
{

    protected $Id = 158;

    protected $Name = 'RetouchHistory';

    protected $FullName = 'Nikon::Main';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Retouch History';

    protected $flag_Permanent = true;

    protected $MaxLength = 10;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'B & W',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Sepia',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Trim',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Small Picture',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'D-Lighting',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Red Eye',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Cyanotype',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Sky Light',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Warm Tone',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Color Custom',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Image Overlay',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Red Intensifier',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Green Intensifier',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Blue Intensifier',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Cross Screen',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Quick Retouch',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'NEF Processing',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Distortion Control',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Fisheye',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Straighten',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Perspective Control',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Color Outline',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Soft Filter',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Resize',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Miniature Effect',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Skin Softening',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Selected Frame',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Color Sketch',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Selective Color',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Drawing',
        ),
    );

}
