<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ArtFilter extends AbstractTag
{

    protected $Id = 1321;

    protected $Name = 'ArtFilter';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Art Filter';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Soft Focus',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Pop Art',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Pale & Light Color',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Light Tone',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Pin Hole',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Grainy Film',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Diorama',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Cross Process',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Fish Eye',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Drawing',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Gentle Sepia',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Pale & Light Color II',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Pop Art II',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Pin Hole II',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Pin Hole III',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Grainy Film II',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Dramatic Tone',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Punk',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Soft Focus 2',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Sparkle',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Watercolor',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Key Line',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Key Line II',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Miniature',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Reflection',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Fragmented',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Cross Process II',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Dramatic Tone II',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Watercolor I',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Watercolor II',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Diorama II',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Vintage',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Vintage II',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Vintage III',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Partial Color',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Partial Color II',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Partial Color III',
        ),
    );

}
