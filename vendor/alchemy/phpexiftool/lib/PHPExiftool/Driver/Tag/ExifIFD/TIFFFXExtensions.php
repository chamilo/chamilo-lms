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
class TIFFFXExtensions extends AbstractTag
{

    protected $Id = 34687;

    protected $Name = 'TIFF_FXExtensions';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'TIFF FX Extensions';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Resolution/Image Width',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'N Layer Profile M',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Shared Data',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'B&W JBIG2',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'JBIG2 Profile M',
        ),
    );

}
