<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Macro',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Infinity',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Super Macro',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Pan Focus',
        ),
        6 => array(
            'Id' => 16,
            'Label' => 'AF-S (Focus-priority)',
        ),
        7 => array(
            'Id' => 17,
            'Label' => 'AF-C (Focus-priority)',
        ),
        8 => array(
            'Id' => 18,
            'Label' => 'AF-A (Focus-priority)',
        ),
        9 => array(
            'Id' => 32,
            'Label' => 'Contrast-detect (Focus-priority)',
        ),
        10 => array(
            'Id' => 33,
            'Label' => 'Tracking Contrast-detect (Focus-priority)',
        ),
        11 => array(
            'Id' => 272,
            'Label' => 'AF-S (Release-priority)',
        ),
        12 => array(
            'Id' => 273,
            'Label' => 'AF-C (Release-priority)',
        ),
        13 => array(
            'Id' => 274,
            'Label' => 'AF-A (Release-priority)',
        ),
        14 => array(
            'Id' => 288,
            'Label' => 'Contrast-detect (Release-priority)',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Macro (1)',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Macro (2)',
        ),
        18 => array(
            'Id' => 3,
            'Label' => 'Infinity',
        ),
        19 => array(
            'Id' => 2,
            'Label' => 'Custom',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Auto',
        ),
    );

    protected $Index = 'mixed';

}
