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
class AFPointSelectionMethod extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointSelectionMethod';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AF Point Selection Method';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Multi-controller direct',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Quick Control Dial direct',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'AF point button: Activate AF Sel; Rear dial: Select AF points',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'AF point button: Auto selection; Rear dial: Manual selection',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Multi-controller direct',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Quick Control Dial direct',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Multi-controller direct',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Quick Control Dial direct',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Multi-controller direct',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Quick Control Dial direct',
        ),
    );

    protected $Index = 'mixed';

}
