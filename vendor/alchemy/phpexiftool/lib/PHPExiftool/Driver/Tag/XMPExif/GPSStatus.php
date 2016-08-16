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
class GPSStatus extends AbstractTag
{

    protected $Id = 'GPSStatus';

    protected $Name = 'GPSStatus';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'GPS Status';

    protected $local_g2 = 'Location';

    protected $Values = array(
        'A' => array(
            'Id' => 'A',
            'Label' => 'Measurement Active',
        ),
        'V' => array(
            'Id' => 'V',
            'Label' => 'Measurement Void',
        ),
    );

}
