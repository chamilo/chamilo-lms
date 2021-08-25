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
class PictureEffect extends AbstractTag
{

    protected $Id = 8206;

    protected $Name = 'PictureEffect';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Picture Effect';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Toy Camera',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Pop Color',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Posterization',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Posterization B/W',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Retro Photo',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Soft High Key',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Partial Color (red)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Partial Color (green)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Partial Color (blue)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Partial Color (yellow)',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'High Contrast Monochrome',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Toy Camera (normal)',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Toy Camera (cool)',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Toy Camera (warm)',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Toy Camera (green)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Toy Camera (magenta)',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Soft Focus (low)',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Soft Focus',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Soft Focus (high)',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Miniature (auto)',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Miniature (top)',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Miniature (middle horizontal)',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Miniature (bottom)',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Miniature (left)',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Miniature (middle vertical)',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'Miniature (right)',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'HDR Painting (low)',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'HDR Painting',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'HDR Painting (high)',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Rich-tone Monochrome',
        ),
        97 => array(
            'Id' => 97,
            'Label' => 'Water Color',
        ),
        98 => array(
            'Id' => 98,
            'Label' => 'Water Color 2',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'Illustration (low)',
        ),
        113 => array(
            'Id' => 113,
            'Label' => 'Illustration',
        ),
        114 => array(
            'Id' => 114,
            'Label' => 'Illustration (high)',
        ),
    );

}
