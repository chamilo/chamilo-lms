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
class ImageQuality extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ImageQuality';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Image Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'NEF (RAW) + JPEG Fine',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'NEF (RAW) + JPEG Norm',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'NEF (RAW) + JPEG Basic',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'NEF (RAW)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'TIF (RGB)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'JPEG Fine',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'JPEG Normal',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'JPEG Basic',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'NEF (RAW)',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'JPEG Fine',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'JPEG Normal',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'JPEG Basic',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'NEF (RAW) + JPEG Fine',
        ),
        13 => array(
            'Id' => 5,
            'Label' => 'NEF (RAW) + JPEG Normal',
        ),
        14 => array(
            'Id' => 6,
            'Label' => 'NEF (RAW) + JPEG Basic',
        ),
    );

}
