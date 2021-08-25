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
class PresetWhiteBalance extends AbstractTag
{

    protected $Id = 36;

    protected $Name = 'PresetWhiteBalance';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Preset White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Cloudy',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Shade',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Tungsten',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Fluorescent',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Flash',
        ),
    );

}
