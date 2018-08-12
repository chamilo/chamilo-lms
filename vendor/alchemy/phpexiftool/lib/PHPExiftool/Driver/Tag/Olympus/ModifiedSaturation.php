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
class ModifiedSaturation extends AbstractTag
{

    protected $Id = 1284;

    protected $Name = 'ModifiedSaturation';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Modified Saturation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'CM1 (Red Enhance)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'CM2 (Green Enhance)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'CM3 (Blue Enhance)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'CM4 (Skin Tones)',
        ),
    );

}
