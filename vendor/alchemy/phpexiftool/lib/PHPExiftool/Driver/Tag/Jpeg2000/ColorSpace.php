<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Jpeg2000;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpace extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'ColorSpace';

    protected $FullName = 'Jpeg2000::ColorSpec';

    protected $GroupName = 'Jpeg2000';

    protected $g0 = 'Jpeg2000';

    protected $g1 = 'Jpeg2000';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Color Space';

    protected $Index = 1;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Bi-level',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'YCbCr(1)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'YCbCr(2)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'YCbCr(3)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'PhotoYCC',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'CMY',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'CMYK',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'YCCK',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'CIELab',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Bi-level(2)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'sRGB',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Grayscale',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'sYCC',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'CIEJab',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'e-sRGB',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'ROMM-RGB',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'YPbPr(1125/60)',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'YPbPr(1250/50)',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'e-sYCC',
        ),
    );

}
