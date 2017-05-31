<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ColorMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Color Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Natural color',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Black & White',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Vivid color',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Solarization',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Adobe RGB',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Landscape',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Sunset',
        ),
        10 => array(
            'Id' => 5,
            'Label' => 'Night Scene',
        ),
        11 => array(
            'Id' => 7,
            'Label' => 'B&W',
        ),
        12 => array(
            'Id' => 8,
            'Label' => 'Adobe RGB',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Natural color',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Black & White',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Vivid color',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'Solarization',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Adobe RGB',
        ),
        18 => array(
            'Id' => 5,
            'Label' => 'Sepia',
        ),
        19 => array(
            'Id' => 9,
            'Label' => 'Natural',
        ),
        20 => array(
            'Id' => 12,
            'Label' => 'Portrait',
        ),
        21 => array(
            'Id' => 13,
            'Label' => 'Natural sRGB',
        ),
        22 => array(
            'Id' => 14,
            'Label' => 'Natural+ sRGB',
        ),
        23 => array(
            'Id' => 15,
            'Label' => 'Landscape',
        ),
        24 => array(
            'Id' => 16,
            'Label' => 'Evening',
        ),
        25 => array(
            'Id' => 17,
            'Label' => 'Night Scene',
        ),
        26 => array(
            'Id' => 18,
            'Label' => 'Night Portrait',
        ),
        27 => array(
            'Id' => 132,
            'Label' => 'Embed Adobe RGB',
        ),
        28 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        29 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        30 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        31 => array(
            'Id' => 3,
            'Label' => 'Landscape',
        ),
        32 => array(
            'Id' => 4,
            'Label' => 'Sunset',
        ),
        33 => array(
            'Id' => 5,
            'Label' => 'Night View/Portrait',
        ),
        34 => array(
            'Id' => 6,
            'Label' => 'B&W',
        ),
        35 => array(
            'Id' => 7,
            'Label' => 'Adobe RGB',
        ),
        36 => array(
            'Id' => 12,
            'Label' => 'Neutral',
        ),
        37 => array(
            'Id' => 13,
            'Label' => 'Clear',
        ),
        38 => array(
            'Id' => 14,
            'Label' => 'Deep',
        ),
        39 => array(
            'Id' => 15,
            'Label' => 'Light',
        ),
        40 => array(
            'Id' => 16,
            'Label' => 'Autumn Leaves',
        ),
        41 => array(
            'Id' => 17,
            'Label' => 'Sepia',
        ),
        42 => array(
            'Id' => 100,
            'Label' => 'Neutral',
        ),
        43 => array(
            'Id' => 101,
            'Label' => 'Clear',
        ),
        44 => array(
            'Id' => 102,
            'Label' => 'Deep',
        ),
        45 => array(
            'Id' => 103,
            'Label' => 'Light',
        ),
        46 => array(
            'Id' => 104,
            'Label' => 'Night View',
        ),
        47 => array(
            'Id' => 105,
            'Label' => 'Autumn Leaves',
        ),
        48 => array(
            'Id' => '4294967295',
            'Label' => 'n/a',
        ),
        49 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        50 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        51 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        52 => array(
            'Id' => 3,
            'Label' => 'Landscape',
        ),
        53 => array(
            'Id' => 4,
            'Label' => 'Sunset',
        ),
        54 => array(
            'Id' => 5,
            'Label' => 'Night View',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'B&W',
        ),
        56 => array(
            'Id' => 8,
            'Label' => 'Adobe RGB',
        ),
    );

    protected $Index = 'mixed';

}
