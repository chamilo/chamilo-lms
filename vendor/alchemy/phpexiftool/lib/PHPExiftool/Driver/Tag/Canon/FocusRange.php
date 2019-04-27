<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusRange extends AbstractTag
{

    protected $Id = 18;

    protected $Name = 'FocusRange';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Focus Range';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Not Known',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Macro',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Very Close',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Close',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Middle Range',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Far Range',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Pan Focus',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Super Macro',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Infinity',
        ),
    );

}
