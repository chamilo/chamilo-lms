<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPointSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        7 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        8 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
        9 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
        10 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'Right',
        ),
        15 => array(
            'Id' => 5,
            'Label' => 'Lower-right',
        ),
        16 => array(
            'Id' => 6,
            'Label' => 'Bottom',
        ),
        17 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        18 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        19 => array(
            'Id' => 9,
            'Label' => 'Upper-left',
        ),
    );

}
