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
class PreviewColorSpace extends AbstractTag
{

    protected $Id = 50970;

    protected $Name = 'PreviewColorSpace';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Preview Color Space';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Gray Gamma 2.2',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'sRGB',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Adobe RGB',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'ProPhoto RGB',
        ),
    );

}
