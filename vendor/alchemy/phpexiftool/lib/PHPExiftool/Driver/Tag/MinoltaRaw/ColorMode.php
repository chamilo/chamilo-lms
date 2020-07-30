<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MinoltaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorMode extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'ColorMode';

    protected $FullName = 'MinoltaRaw::RIF';

    protected $GroupName = 'MinoltaRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'MinoltaRaw';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

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
            'Id' => 5,
            'Label' => 'Sepia',
        ),
        6 => array(
            'Id' => 9,
            'Label' => 'Natural',
        ),
        7 => array(
            'Id' => 12,
            'Label' => 'Portrait',
        ),
        8 => array(
            'Id' => 13,
            'Label' => 'Natural sRGB',
        ),
        9 => array(
            'Id' => 14,
            'Label' => 'Natural+ sRGB',
        ),
        10 => array(
            'Id' => 15,
            'Label' => 'Landscape',
        ),
        11 => array(
            'Id' => 16,
            'Label' => 'Evening',
        ),
        12 => array(
            'Id' => 17,
            'Label' => 'Night Scene',
        ),
        13 => array(
            'Id' => 18,
            'Label' => 'Night Portrait',
        ),
        14 => array(
            'Id' => 132,
            'Label' => 'Embed Adobe RGB',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        18 => array(
            'Id' => 3,
            'Label' => 'Landscape',
        ),
        19 => array(
            'Id' => 4,
            'Label' => 'Sunset',
        ),
        20 => array(
            'Id' => 5,
            'Label' => 'Night View/Portrait',
        ),
        21 => array(
            'Id' => 6,
            'Label' => 'B&W',
        ),
        22 => array(
            'Id' => 7,
            'Label' => 'Adobe RGB',
        ),
        23 => array(
            'Id' => 12,
            'Label' => 'Neutral',
        ),
        24 => array(
            'Id' => 13,
            'Label' => 'Clear',
        ),
        25 => array(
            'Id' => 14,
            'Label' => 'Deep',
        ),
        26 => array(
            'Id' => 15,
            'Label' => 'Light',
        ),
        27 => array(
            'Id' => 16,
            'Label' => 'Autumn Leaves',
        ),
        28 => array(
            'Id' => 17,
            'Label' => 'Sepia',
        ),
        29 => array(
            'Id' => 100,
            'Label' => 'Neutral',
        ),
        30 => array(
            'Id' => 101,
            'Label' => 'Clear',
        ),
        31 => array(
            'Id' => 102,
            'Label' => 'Deep',
        ),
        32 => array(
            'Id' => 103,
            'Label' => 'Light',
        ),
        33 => array(
            'Id' => 104,
            'Label' => 'Night View',
        ),
        34 => array(
            'Id' => 105,
            'Label' => 'Autumn Leaves',
        ),
        35 => array(
            'Id' => '4294967295',
            'Label' => 'n/a',
        ),
    );

    protected $Index = 'mixed';

}
