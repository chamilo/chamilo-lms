<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SonyIDC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PresetWhiteBalance extends AbstractTag
{

    protected $Id = 32770;

    protected $Name = 'PresetWhiteBalance';

    protected $FullName = 'SonyIDC::Main';

    protected $GroupName = 'SonyIDC';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'SonyIDC';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Preset White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Camera Setting',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Color Temperature',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Specify Gray Point',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Daylight',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Cloudy',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Shade',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Cool White Fluorescent',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Day Light Fluorescent',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Day White Fluorescent',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Warm White Fluorescent',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Tungsten',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Flash',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Auto',
        ),
    );

}
