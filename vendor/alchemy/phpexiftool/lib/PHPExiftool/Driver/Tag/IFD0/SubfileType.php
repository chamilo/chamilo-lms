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
class SubfileType extends AbstractTag
{

    protected $Id = 254;

    protected $Name = 'SubfileType';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Subfile Type';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Full-resolution Image',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Reduced-resolution image',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Single page of multi-page image',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Single page of multi-page reduced-resolution image',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Transparency mask',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Transparency mask of reduced-resolution image',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Transparency mask of multi-page image',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Transparency mask of reduced-resolution multi-page image',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'TIFF/IT final page',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'TIFF-FX mixed raster content',
        ),
        65537 => array(
            'Id' => 65537,
            'Label' => 'Alternate reduced-resolution image',
        ),
        '4294967295' => array(
            'Id' => '4294967295',
            'Label' => 'invalid',
        ),
    );

}
