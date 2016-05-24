<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class AFAreaMode extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'AFAreaMode';

    protected $FullName = 'Canon::AFInfo2';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'AF Area Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off (Manual Focus)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Single-point AF',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Multi-point AF or AI AF',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Face Detect AF',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Face + Tracking',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Zone AF',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'AF Point Expansion',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Spot AF',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Flexizone Multi',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Flexizone Single',
        ),
    );

}
