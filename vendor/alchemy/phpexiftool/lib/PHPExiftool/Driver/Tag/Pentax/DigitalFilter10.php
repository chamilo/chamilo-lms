<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DigitalFilter10 extends AbstractTag
{

    protected $Id = 158;

    protected $Name = 'DigitalFilter10';

    protected $FullName = 'Pentax::FilterInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Digital Filter 10';

    protected $flag_Permanent = true;

    protected $MaxLength = 17;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Base Parameter Adjust',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Soft Focus',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'High Contrast',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Color Filter',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Extract Color',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Monochrome',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Slim',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Fisheye',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Toy Camera',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Retro',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Pastel',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Water Color',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'HDR',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Miniature',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Starburst',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Posterization',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Sketch Filter',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Shading',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Invert Color',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Tone Expansion',
        ),
        254 => array(
            'Id' => 254,
            'Label' => 'Custom Filter',
        ),
    );

}
