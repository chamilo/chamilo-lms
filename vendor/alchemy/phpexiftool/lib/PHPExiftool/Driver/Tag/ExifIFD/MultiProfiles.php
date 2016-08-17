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
class MultiProfiles extends AbstractTag
{

    protected $Id = 34688;

    protected $Name = 'MultiProfiles';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Multi Profiles';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Profile S',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Profile F',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Profile J',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Profile C',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Profile L',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Profile M',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Profile T',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Resolution/Image Width',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'N Layer Profile M',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Shared Data',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'JBIG2 Profile M',
        ),
    );

}
