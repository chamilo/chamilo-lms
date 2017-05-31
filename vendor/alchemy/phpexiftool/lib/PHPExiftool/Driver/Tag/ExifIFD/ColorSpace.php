<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpace extends AbstractTag
{

    protected $Id = 40961;

    protected $Name = 'ColorSpace';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Color Space';

    protected $local_g1 = 'ExifIFD';

    protected $flag_Mandatory = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'sRGB',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Adobe RGB',
        ),
        65533 => array(
            'Id' => 65533,
            'Label' => 'Wide Gamut RGB',
        ),
        65534 => array(
            'Id' => 65534,
            'Label' => 'ICC Profile',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'Uncalibrated',
        ),
    );

}
