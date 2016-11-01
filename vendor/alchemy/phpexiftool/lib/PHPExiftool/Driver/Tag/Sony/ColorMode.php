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
class ColorMode extends AbstractTag
{

    protected $Id = 45097;

    protected $Name = 'ColorMode';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Color Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Landscape',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Sunset',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Night View/Portrait',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'B&W',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Adobe RGB',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Neutral',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Clear',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Deep',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Light',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Autumn Leaves',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Sepia',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'Neutral',
        ),
        101 => array(
            'Id' => 101,
            'Label' => 'Clear',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'Deep',
        ),
        103 => array(
            'Id' => 103,
            'Label' => 'Light',
        ),
        104 => array(
            'Id' => 104,
            'Label' => 'Night View',
        ),
        105 => array(
            'Id' => 105,
            'Label' => 'Autumn Leaves',
        ),
        '4294967295' => array(
            'Id' => '4294967295',
            'Label' => 'n/a',
        ),
    );

}
