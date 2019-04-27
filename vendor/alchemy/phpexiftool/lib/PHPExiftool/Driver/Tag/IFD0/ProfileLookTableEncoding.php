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
class ProfileLookTableEncoding extends AbstractTag
{

    protected $Id = 51108;

    protected $Name = 'ProfileLookTableEncoding';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Profile Look Table Encoding';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Linear',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'sRGB',
        ),
    );

}
