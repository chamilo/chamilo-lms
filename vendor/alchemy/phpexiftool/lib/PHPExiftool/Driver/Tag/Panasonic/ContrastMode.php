<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ContrastMode extends AbstractTag
{

    protected $Id = 44;

    protected $Name = 'ContrastMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Contrast Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 6,
            'Label' => 'Medium Low',
        ),
        4 => array(
            'Id' => 7,
            'Label' => 'Medium High',
        ),
        5 => array(
            'Id' => 13,
            'Label' => 'High Dynamic',
        ),
        6 => array(
            'Id' => 256,
            'Label' => 'Low',
        ),
        7 => array(
            'Id' => 272,
            'Label' => 'Normal',
        ),
        8 => array(
            'Id' => 288,
            'Label' => 'High',
        ),
        9 => array(
            'Id' => 0,
            'Label' => '-2',
        ),
        10 => array(
            'Id' => 1,
            'Label' => '-1',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        12 => array(
            'Id' => 3,
            'Label' => '+1',
        ),
        13 => array(
            'Id' => 4,
            'Label' => '+2',
        ),
        14 => array(
            'Id' => 5,
            'Label' => 'Normal 2',
        ),
        15 => array(
            'Id' => 7,
            'Label' => 'Nature (Color Film)',
        ),
        16 => array(
            'Id' => 9,
            'Label' => 'Expressive',
        ),
        17 => array(
            'Id' => 12,
            'Label' => 'Smooth (Color Film) or Pure (My Color)',
        ),
        18 => array(
            'Id' => 17,
            'Label' => 'Dynamic (B&W Film)',
        ),
        19 => array(
            'Id' => 22,
            'Label' => 'Smooth (B&W Film)',
        ),
        20 => array(
            'Id' => 25,
            'Label' => 'High Dynamic',
        ),
        21 => array(
            'Id' => 26,
            'Label' => 'Retro',
        ),
        22 => array(
            'Id' => 27,
            'Label' => 'Dynamic (Color Film)',
        ),
        23 => array(
            'Id' => 28,
            'Label' => 'Low Key',
        ),
        24 => array(
            'Id' => 29,
            'Label' => 'Toy Effect',
        ),
        25 => array(
            'Id' => 32,
            'Label' => 'Vibrant (Color Film) or Expressive (My Color)',
        ),
        26 => array(
            'Id' => 33,
            'Label' => 'Elegant (My Color)',
        ),
        27 => array(
            'Id' => 37,
            'Label' => 'Nostalgic (Color Film)',
        ),
        28 => array(
            'Id' => 41,
            'Label' => 'Dynamic Art (My Color)',
        ),
        29 => array(
            'Id' => 42,
            'Label' => 'Retro (My Color)',
        ),
        30 => array(
            'Id' => 45,
            'Label' => 'Cinema',
        ),
        31 => array(
            'Id' => 47,
            'Label' => 'Dynamic Mono',
        ),
        32 => array(
            'Id' => 50,
            'Label' => 'Impressive Art',
        ),
        33 => array(
            'Id' => 51,
            'Label' => 'Cross Process',
        ),
        34 => array(
            'Id' => 100,
            'Label' => 'High Dynamic 2',
        ),
        35 => array(
            'Id' => 101,
            'Label' => 'Retro 2',
        ),
        36 => array(
            'Id' => 102,
            'Label' => 'High Key 2',
        ),
        37 => array(
            'Id' => 103,
            'Label' => 'Low Key 2',
        ),
        38 => array(
            'Id' => 104,
            'Label' => 'Toy Effect 2',
        ),
        39 => array(
            'Id' => 107,
            'Label' => 'Expressive 2',
        ),
        40 => array(
            'Id' => 112,
            'Label' => 'Sepia',
        ),
        41 => array(
            'Id' => 117,
            'Label' => 'Miniature',
        ),
        42 => array(
            'Id' => 122,
            'Label' => 'Dynamic Monochrome',
        ),
        43 => array(
            'Id' => 127,
            'Label' => 'Old Days',
        ),
        44 => array(
            'Id' => 132,
            'Label' => 'Dynamic Monochrome 2',
        ),
        45 => array(
            'Id' => 135,
            'Label' => 'Impressive Art 2',
        ),
        46 => array(
            'Id' => 136,
            'Label' => 'Cross Process 2',
        ),
        47 => array(
            'Id' => 137,
            'Label' => 'Toy Pop',
        ),
        48 => array(
            'Id' => 138,
            'Label' => 'Fantasy',
        ),
        49 => array(
            'Id' => 256,
            'Label' => 'Normal 3',
        ),
        50 => array(
            'Id' => 272,
            'Label' => 'Standard',
        ),
        51 => array(
            'Id' => 288,
            'Label' => 'High',
        ),
        52 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        53 => array(
            'Id' => 1,
            'Label' => '-2',
        ),
        54 => array(
            'Id' => 2,
            'Label' => '+2',
        ),
        55 => array(
            'Id' => 5,
            'Label' => '-1',
        ),
        56 => array(
            'Id' => 6,
            'Label' => '+1',
        ),
    );

    protected $Index = 'mixed';

}
