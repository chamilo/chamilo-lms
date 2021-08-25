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
class WhiteBalance2 extends AbstractTag
{

    protected $Id = 1280;

    protected $Name = 'WhiteBalance2';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'White Balance 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto (Keep Warm Color Off)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '7500K (Fine Weather with Shade)',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '6000K (Cloudy)',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '5300K (Fine Weather)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '3000K (Tungsten light)',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '3600K (Tungsten light-like)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Auto Setup',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '5500K (Flash)',
        ),
        33 => array(
            'Id' => 33,
            'Label' => '6600K (Daylight fluorescent)',
        ),
        34 => array(
            'Id' => 34,
            'Label' => '4500K (Neutral white fluorescent)',
        ),
        35 => array(
            'Id' => 35,
            'Label' => '4000K (Cool white fluorescent)',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'White Fluorescent',
        ),
        48 => array(
            'Id' => 48,
            'Label' => '3600K (Tungsten light-like)',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Underwater',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'One Touch WB 1',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'One Touch WB 2',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'One Touch WB 3',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'One Touch WB 4',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Custom WB 1',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Custom WB 2',
        ),
        514 => array(
            'Id' => 514,
            'Label' => 'Custom WB 3',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Custom WB 4',
        ),
    );

}
