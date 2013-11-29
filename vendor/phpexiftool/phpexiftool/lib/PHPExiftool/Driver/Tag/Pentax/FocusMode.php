<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
            'Label' => 'AF-S',
        ),
        7 => array(
            'Id' => 17,
            'Label' => 'AF-C',
        ),
        8 => array(
            'Id' => 18,
            'Label' => 'AF-A',
        ),
        9 => array(
            'Id' => 32,
            'Label' => 'Contrast-detect',
        ),
        10 => array(
            'Id' => 33,
            'Label' => 'Tracking Contrast-detect',
        ),
        11 => array(
            'Id' => 288,
            'Label' => 'Face Detect',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Macro (1)',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Macro (2)',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'Infinity',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Custom',
        ),
        17 => array(
            'Id' => 3,
            'Label' => 'Auto',
        ),
    );

    protected $Index = 'mixed';

}
