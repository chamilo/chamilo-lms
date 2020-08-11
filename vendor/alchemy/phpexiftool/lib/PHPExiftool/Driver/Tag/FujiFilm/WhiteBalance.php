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
class WhiteBalance extends AbstractTag
{

    protected $Id = 4098;

    protected $Name = 'WhiteBalance';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Daylight',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Cloudy',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Daylight Fluorescent',
        ),
        769 => array(
            'Id' => 769,
            'Label' => 'Day White Fluorescent',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'White Fluorescent',
        ),
        771 => array(
            'Id' => 771,
            'Label' => 'Warm White Fluorescent',
        ),
        772 => array(
            'Id' => 772,
            'Label' => 'Living Room Warm White Fluorescent',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Incandescent',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Flash',
        ),
        1536 => array(
            'Id' => 1536,
            'Label' => 'Underwater',
        ),
        3840 => array(
            'Id' => 3840,
            'Label' => 'Custom',
        ),
        3841 => array(
            'Id' => 3841,
            'Label' => 'Custom2',
        ),
        3842 => array(
            'Id' => 3842,
            'Label' => 'Custom3',
        ),
        3843 => array(
            'Id' => 3843,
            'Label' => 'Custom4',
        ),
        3844 => array(
            'Id' => 3844,
            'Label' => 'Custom5',
        ),
        4080 => array(
            'Id' => 4080,
            'Label' => 'Kelvin',
        ),
    );

}
