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
class FlashExposureIndicatorLast extends AbstractTag
{

    protected $Id = 86;

    protected $Name = 'FlashExposureIndicatorLast';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Flash Exposure Indicator Last';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not Indicated',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Under Scale',
        ),
        119 => array(
            'Id' => 119,
            'Label' => 'Bottom of Scale',
        ),
        120 => array(
            'Id' => 120,
            'Label' => '-2.0',
        ),
        121 => array(
            'Id' => 121,
            'Label' => '-1.7',
        ),
        122 => array(
            'Id' => 122,
            'Label' => '-1.5',
        ),
        123 => array(
            'Id' => 123,
            'Label' => '-1.3',
        ),
        124 => array(
            'Id' => 124,
            'Label' => '-1.0',
        ),
        125 => array(
            'Id' => 125,
            'Label' => '-0.7',
        ),
        126 => array(
            'Id' => 126,
            'Label' => '-0.5',
        ),
        127 => array(
            'Id' => 127,
            'Label' => '-0.3',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 0,
        ),
        129 => array(
            'Id' => 129,
            'Label' => '+0.3',
        ),
        130 => array(
            'Id' => 130,
            'Label' => '+0.5',
        ),
        131 => array(
            'Id' => 131,
            'Label' => '+0.7',
        ),
        132 => array(
            'Id' => 132,
            'Label' => '+1.0',
        ),
        133 => array(
            'Id' => 133,
            'Label' => '+1.3',
        ),
        134 => array(
            'Id' => 134,
            'Label' => '+1.5',
        ),
        135 => array(
            'Id' => 135,
            'Label' => '+1.7',
        ),
        136 => array(
            'Id' => 136,
            'Label' => '+2.0',
        ),
        253 => array(
            'Id' => 253,
            'Label' => 'Top of Scale',
        ),
        254 => array(
            'Id' => 254,
            'Label' => 'Over Scale',
        ),
    );

}
