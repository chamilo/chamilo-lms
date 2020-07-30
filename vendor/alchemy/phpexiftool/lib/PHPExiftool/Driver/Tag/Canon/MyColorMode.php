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
class MyColorMode extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'MyColorMode';

    protected $FullName = 'Canon::MyColors';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'My Color Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Positive Film',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Light Skin Tone',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Dark Skin Tone',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Vivid Blue',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Vivid Green',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Vivid Red',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Color Accent',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Color Swap',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Custom',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Vivid',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Neutral',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Sepia',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'B&W',
        ),
    );

}
