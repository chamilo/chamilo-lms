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
class ModifiedPictureStyle extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'ModifiedPictureStyle';

    protected $FullName = 'Canon::ModifiedInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Modified Picture Style';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'High Saturation',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Adobe RGB',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Low Saturation',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'CM Set 1',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'CM Set 2',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'User Def. 1',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'User Def. 2',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'User Def. 3',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'PC 1',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'PC 2',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'PC 3',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Standard',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Portrait',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Landscape',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'Neutral',
        ),
        133 => array(
            'Id' => 133,
            'Label' => 'Faithful',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'Monochrome',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'Auto',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Fine Detail',
        ),
    );

}
