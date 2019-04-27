<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorFilter extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ColorFilter';

    protected $FullName = 'mixed';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Color Filter';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Black & White',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Sepia',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Red',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Green',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Blue',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'Yellow',
        ),
        7 => array(
            'Id' => 8,
            'Label' => 'Pink',
        ),
        8 => array(
            'Id' => 9,
            'Label' => 'Purple',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Blue',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'Green',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'Yellow',
        ),
        13 => array(
            'Id' => 5,
            'Label' => 'Red',
        ),
        14 => array(
            'Id' => 6,
            'Label' => 'Purple',
        ),
        15 => array(
            'Id' => 7,
            'Label' => 'Pink',
        ),
    );

}
