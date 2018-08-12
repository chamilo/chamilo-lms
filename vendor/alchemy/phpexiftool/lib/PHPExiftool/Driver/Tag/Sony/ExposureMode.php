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
class ExposureMode extends AbstractTag
{

    protected $Id = 45121;

    protected $Name = 'ExposureMode';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Exposure Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Beach',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Sports',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Snow',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Landscape',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Auto',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Aperture-priority AE',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Shutter speed priority AE',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Night Scene / Twilight',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Hi-Speed Shutter',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Soft Snap/Portrait',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Fireworks',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Smile Shutter',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Manual',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'High Sensitivity',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Macro',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Advanced Sports Shooting',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Underwater',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Food',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Sweep Panorama',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Handheld Night Shot',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Anti Motion Blur',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Pet',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Backlight Correction HDR',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Superior Auto',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Background Defocus',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Soft Skin',
        ),
        42 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
    );

}
