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
class SelectableAFPoint extends AbstractTag
{

    protected $Id = 1289;

    protected $Name = 'SelectableAFPoint';

    protected $FullName = 'CanonCustom::Functions2';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Selectable AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '45 points',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '19 points',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '11 points',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Inner 9 points',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Outer 9 points',
        ),
        5 => array(
            'Id' => 0,
            'Label' => '19 points',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Inner 9 points',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Outer 9 points',
        ),
        8 => array(
            'Id' => 3,
            'Label' => '19 Points, Multi-controller selectable',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Inner 9 Points, Multi-controller selectable',
        ),
        10 => array(
            'Id' => 5,
            'Label' => 'Outer 9 Points, Multi-controller selectable',
        ),
    );

    protected $Index = 'mixed';

}
