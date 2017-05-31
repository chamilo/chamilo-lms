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
class MeteringMode extends AbstractTag
{

    protected $Id = 514;

    protected $Name = 'MeteringMode';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Metering Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'ESP',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'Pattern+AF',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Spot+Highlight control',
        ),
        1027 => array(
            'Id' => 1027,
            'Label' => 'Spot+Shadow control',
        ),
    );

}
