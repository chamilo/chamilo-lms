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
class ExposureMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Exposure Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Aperture Priority',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Shutter Priority',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Aperture Priority',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Shutter Priority',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'Auto?',
        ),
        9 => array(
            'Id' => 4131,
            'Label' => 'Connected Copying?',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Aperture Priority',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Shutter Priority',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        15 => array(
            'Id' => 5,
            'Label' => 'Program-shift A',
        ),
        16 => array(
            'Id' => 6,
            'Label' => 'Program-shift S',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        18 => array(
            'Id' => 1,
            'Label' => 'Aperture Priority',
        ),
        19 => array(
            'Id' => 2,
            'Label' => 'Shutter Priority',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        21 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        22 => array(
            'Id' => 5,
            'Label' => 'Program Shift A',
        ),
        23 => array(
            'Id' => 6,
            'Label' => 'Program Shift S',
        ),
        24 => array(
            'Id' => 4115,
            'Label' => 'Portrait',
        ),
        25 => array(
            'Id' => 4131,
            'Label' => 'Sports',
        ),
        26 => array(
            'Id' => 4147,
            'Label' => 'Sunset',
        ),
        27 => array(
            'Id' => 4163,
            'Label' => 'Night View/Portrait',
        ),
        28 => array(
            'Id' => 4179,
            'Label' => 'Landscape',
        ),
        29 => array(
            'Id' => 4227,
            'Label' => 'Macro',
        ),
        30 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        31 => array(
            'Id' => 1,
            'Label' => 'Aperture Priority',
        ),
        32 => array(
            'Id' => 2,
            'Label' => 'Shutter Priority',
        ),
        33 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        34 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        35 => array(
            'Id' => 5,
            'Label' => 'Program Shift A',
        ),
        36 => array(
            'Id' => 6,
            'Label' => 'Program Shift S',
        ),
        37 => array(
            'Id' => 4115,
            'Label' => 'Portrait',
        ),
        38 => array(
            'Id' => 4131,
            'Label' => 'Sports',
        ),
        39 => array(
            'Id' => 4147,
            'Label' => 'Sunset',
        ),
        40 => array(
            'Id' => 4163,
            'Label' => 'Night View/Portrait',
        ),
        41 => array(
            'Id' => 4179,
            'Label' => 'Landscape',
        ),
        42 => array(
            'Id' => 4227,
            'Label' => 'Macro',
        ),
    );

}
