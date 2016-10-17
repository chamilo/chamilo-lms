<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AIServoTrackingSensitivity extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AIServoTrackingSensitivity';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AI Servo Tracking Sensitivity';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Slow',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Moderately slow',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Moderately fast',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Fast',
        ),
        5 => array(
            'Id' => '-2',
            'Label' => 'Slow',
        ),
        6 => array(
            'Id' => '-1',
            'Label' => 'Medium Slow',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Medium Fast',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Fast',
        ),
    );

}
