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
class UserDef2PictureStyle extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'UserDef2PictureStyle';

    protected $FullName = 'mixed';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'User Def 2 Picture Style';

    protected $flag_Permanent = true;

    protected $Values = array(
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
    );

}
