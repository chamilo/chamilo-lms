<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBAdjLighting extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'WBAdjLighting';

    protected $FullName = 'NikonCapture::WBAdjData';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'WB Adj Lighting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Incandescent',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Daylight (direct sunlight)',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Daylight (shade)',
        ),
        514 => array(
            'Id' => 514,
            'Label' => 'Daylight (cloudy)',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Standard Fluorescent (warm white)',
        ),
        769 => array(
            'Id' => 769,
            'Label' => 'Standard Fluorescent (3700K)',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'Standard Fluorescent (cool white)',
        ),
        771 => array(
            'Id' => 771,
            'Label' => 'Standard Fluorescent (5000K)',
        ),
        772 => array(
            'Id' => 772,
            'Label' => 'Standard Fluorescent (daylight)',
        ),
        773 => array(
            'Id' => 773,
            'Label' => 'Standard Fluorescent (high temperature mercury vapor)',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'High Color Rendering Fluorescent (warm white)',
        ),
        1025 => array(
            'Id' => 1025,
            'Label' => 'High Color Rendering Fluorescent (3700K)',
        ),
        1026 => array(
            'Id' => 1026,
            'Label' => 'High Color Rendering Fluorescent (cool white)',
        ),
        1027 => array(
            'Id' => 1027,
            'Label' => 'High Color Rendering Fluorescent (5000K)',
        ),
        1028 => array(
            'Id' => 1028,
            'Label' => 'High Color Rendering Fluorescent (daylight)',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Flash',
        ),
        1281 => array(
            'Id' => 1281,
            'Label' => 'Flash (FL-G1 filter)',
        ),
        1282 => array(
            'Id' => 1282,
            'Label' => 'Flash (FL-G2 filter)',
        ),
        1283 => array(
            'Id' => 1283,
            'Label' => 'Flash (TN-A1 filter)',
        ),
        1284 => array(
            'Id' => 1284,
            'Label' => 'Flash (TN-A2 filter)',
        ),
        1536 => array(
            'Id' => 1536,
            'Label' => 'Sodium Vapor Lamps',
        ),
    );

}
