<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorType extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'ColorType';

    protected $FullName = 'PNG::ImageHeader';

    protected $GroupName = 'PNG';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Color Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Grayscale',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RGB',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Palette',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Grayscale with Alpha',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'RGB with Alpha',
        ),
    );

}
