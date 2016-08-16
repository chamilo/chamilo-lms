<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPointBrightness extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointBrightness';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point Brightness';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Low',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 6,
            'Label' => 'Extra High',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Low',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'Extra High',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'Off',
        ),
    );

}
