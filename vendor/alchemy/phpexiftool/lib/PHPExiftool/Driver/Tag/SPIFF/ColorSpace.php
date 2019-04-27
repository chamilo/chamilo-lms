<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SPIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpace extends AbstractTag
{

    protected $Id = 14;

    protected $Name = 'ColorSpace';

    protected $FullName = 'JPEG::SPIFF';

    protected $GroupName = 'SPIFF';

    protected $g0 = 'APP8';

    protected $g1 = 'SPIFF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Color Space';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Bi-level',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'YCbCr, ITU-R BT 709, video',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'No color space specified',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'YCbCr, ITU-R BT 601-1, RGB',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'YCbCr, ITU-R BT 601-1, video',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Gray-scale',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'PhotoYCC',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'RGB',
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
    );

}
