<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpace extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ColorSpace';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Color Space';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Natural sRGB',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Natural+ sRGB',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Monochrome',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Adobe RGB (ICC)',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Adobe RGB',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Natural sRGB',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Natural+ sRGB',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'Adobe RGB',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'sRGB',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'B&W',
        ),
        10 => array(
            'Id' => 5,
            'Label' => 'Adobe RGB',
        ),
    );

}
