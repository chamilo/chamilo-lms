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
class CreativeStyle extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CreativeStyle';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Creative Style';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Vivid',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Landscape',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Sunset',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Night View/Portrait',
        ),
        6 => array(
            'Id' => 8,
            'Label' => 'B&W',
        ),
        7 => array(
            'Id' => 9,
            'Label' => 'Adobe RGB',
        ),
        8 => array(
            'Id' => 11,
            'Label' => 'Neutral',
        ),
        9 => array(
            'Id' => 12,
            'Label' => 'Clear',
        ),
        10 => array(
            'Id' => 13,
            'Label' => 'Deep',
        ),
        11 => array(
            'Id' => 14,
            'Label' => 'Light',
        ),
        12 => array(
            'Id' => 15,
            'Label' => 'Autumn Leaves',
        ),
        13 => array(
            'Id' => 16,
            'Label' => 'Sepia',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Vivid',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Landscape',
        ),
        18 => array(
            'Id' => 5,
            'Label' => 'Sunset',
        ),
        19 => array(
            'Id' => 6,
            'Label' => 'Night View/Portrait',
        ),
        20 => array(
            'Id' => 8,
            'Label' => 'B&W',
        ),
        21 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        22 => array(
            'Id' => 2,
            'Label' => 'Vivid',
        ),
        23 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        24 => array(
            'Id' => 4,
            'Label' => 'Landscape',
        ),
        25 => array(
            'Id' => 5,
            'Label' => 'Sunset',
        ),
        26 => array(
            'Id' => 6,
            'Label' => 'Night View/Portrait',
        ),
        27 => array(
            'Id' => 8,
            'Label' => 'B&W',
        ),
        28 => array(
            'Id' => 9,
            'Label' => 'Adobe RGB',
        ),
        29 => array(
            'Id' => 11,
            'Label' => 'Neutral',
        ),
        30 => array(
            'Id' => 12,
            'Label' => 'Clear',
        ),
        31 => array(
            'Id' => 13,
            'Label' => 'Deep',
        ),
        32 => array(
            'Id' => 14,
            'Label' => 'Light',
        ),
        33 => array(
            'Id' => 15,
            'Label' => 'Autumn Leaves',
        ),
        34 => array(
            'Id' => 16,
            'Label' => 'Sepia',
        ),
        'AdobeRGB' => array(
            'Id' => 'AdobeRGB',
            'Label' => 'Adobe RGB',
        ),
        'Autumnleaves' => array(
            'Id' => 'Autumnleaves',
            'Label' => 'Autumn Leaves',
        ),
        'BW' => array(
            'Id' => 'BW',
            'Label' => 'B&W',
        ),
        'Clear' => array(
            'Id' => 'Clear',
            'Label' => 'Clear',
        ),
        'Deep' => array(
            'Id' => 'Deep',
            'Label' => 'Deep',
        ),
        'Landscape' => array(
            'Id' => 'Landscape',
            'Label' => 'Landscape',
        ),
        'Light' => array(
            'Id' => 'Light',
            'Label' => 'Light',
        ),
        'Neutral' => array(
            'Id' => 'Neutral',
            'Label' => 'Neutral',
        ),
        'Nightview' => array(
            'Id' => 'Nightview',
            'Label' => 'Night View/Portrait',
        ),
        'None' => array(
            'Id' => 'None',
            'Label' => 'None',
        ),
        'Portrait' => array(
            'Id' => 'Portrait',
            'Label' => 'Portrait',
        ),
        'Real' => array(
            'Id' => 'Real',
            'Label' => 'Real',
        ),
        'Sepia' => array(
            'Id' => 'Sepia',
            'Label' => 'Sepia',
        ),
        'Standard' => array(
            'Id' => 'Standard',
            'Label' => 'Standard',
        ),
        'Sunset' => array(
            'Id' => 'Sunset',
            'Label' => 'Sunset',
        ),
        'Vivid' => array(
            'Id' => 'Vivid',
            'Label' => 'Vivid',
        ),
        35 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        36 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        37 => array(
            'Id' => 2,
            'Label' => 'Neutral',
        ),
        38 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        39 => array(
            'Id' => 4,
            'Label' => 'Landscape',
        ),
        40 => array(
            'Id' => 5,
            'Label' => 'B&W',
        ),
        41 => array(
            'Id' => 6,
            'Label' => 'Clear',
        ),
        42 => array(
            'Id' => 7,
            'Label' => 'Deep',
        ),
        43 => array(
            'Id' => 8,
            'Label' => 'Light',
        ),
        44 => array(
            'Id' => 9,
            'Label' => 'Sunset',
        ),
        45 => array(
            'Id' => 10,
            'Label' => 'Night View/Portrait',
        ),
        46 => array(
            'Id' => 11,
            'Label' => 'Autumn Leaves',
        ),
        47 => array(
            'Id' => 13,
            'Label' => 'Sepia',
        ),
    );

}
