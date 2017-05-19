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
class LightSource extends AbstractTag
{

    protected $Id = 4096;

    protected $Name = 'LightSource';

    protected $FullName = 'Olympus::RawInfo';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Light Source';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Shade',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Cloudy',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Fine Weather',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Tungsten (Incandescent)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Evening Sunlight',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Daylight Fluorescent',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Day White Fluorescent',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Cool White Fluorescent',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'White Fluorescent',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'One Touch White Balance',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Custom 1-4',
        ),
    );

}
