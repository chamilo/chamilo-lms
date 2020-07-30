<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorEffect extends AbstractTag
{

    protected $Id = 40;

    protected $Name = 'ColorEffect';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Color Effect';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Warm',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Cool',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Black & White',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Sepia',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Happy',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Vivid',
        ),
    );

}
