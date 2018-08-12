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
class VerticalAFOnButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'VerticalAFOnButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Vertical AF On Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF On',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'AE/AF Lock',
        ),
        2 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        3 => array(
            'Id' => 48,
            'Label' => 'AE Lock (reset on release)',
        ),
        4 => array(
            'Id' => 64,
            'Label' => 'AE Lock (hold)',
        ),
        5 => array(
            'Id' => 80,
            'Label' => 'AF Lock Only',
        ),
        6 => array(
            'Id' => 112,
            'Label' => 'Same as AF On',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Same as AF On',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'AF On',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'AE/AF Lock',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'AE Lock Only',
        ),
        11 => array(
            'Id' => 4,
            'Label' => 'AE Lock (reset on release)',
        ),
        12 => array(
            'Id' => 5,
            'Label' => 'AE Lock (hold)',
        ),
        13 => array(
            'Id' => 6,
            'Label' => 'AF Lock Only',
        ),
        14 => array(
            'Id' => 7,
            'Label' => 'None',
        ),
    );

}
