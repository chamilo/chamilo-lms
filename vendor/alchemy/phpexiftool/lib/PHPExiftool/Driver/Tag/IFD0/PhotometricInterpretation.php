<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PhotometricInterpretation extends AbstractTag
{

    protected $Id = 262;

    protected $Name = 'PhotometricInterpretation';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Photometric Interpretation';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'WhiteIsZero',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'BlackIsZero',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RGB',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'RGB Palette',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Transparency Mask',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'CMYK',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'YCbCr',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'CIELab',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'ICCLab',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'ITULab',
        ),
        32803 => array(
            'Id' => 32803,
            'Label' => 'Color Filter Array',
        ),
        32844 => array(
            'Id' => 32844,
            'Label' => 'Pixar LogL',
        ),
        32845 => array(
            'Id' => 32845,
            'Label' => 'Pixar LogLuv',
        ),
        34892 => array(
            'Id' => 34892,
            'Label' => 'Linear Raw',
        ),
    );

}
