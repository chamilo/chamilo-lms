<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiFilm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PictureMode extends AbstractTag
{

    protected $Id = 4145;

    protected $Name = 'PictureMode';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Picture Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Landscape',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Macro',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Sports',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Night Scene',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Program AE',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Natural Light',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Anti-blur',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Beach & Snow',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Sunset',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Museum',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Party',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Flower',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Text',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Natural Light & Flash',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Beach',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Snow',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Fireworks',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Underwater',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Portrait with Skin Correction',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Panorama',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Night (tripod)',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Pro Low-light',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Pro Focus',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Portrait 2',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Dog Face Detection',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Cat Face Detection',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Advanced Filter',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Aperture-priority AE',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Shutter speed priority AE',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Manual',
        ),
    );

}
