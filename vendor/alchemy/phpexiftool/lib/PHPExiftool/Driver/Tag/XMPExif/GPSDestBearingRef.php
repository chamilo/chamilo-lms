<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExif;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSDestBearingRef extends AbstractTag
{

    protected $Id = 'GPSDestBearingRef';

    protected $Name = 'GPSDestBearingRef';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'GPS Dest Bearing Ref';

    protected $local_g2 = 'Location';

    protected $Values = array(
        'M' => array(
            'Id' => 'M',
            'Label' => 'Magnetic North',
        ),
        'T' => array(
            'Id' => 'T',
            'Label' => 'True North',
        ),
    );

}
