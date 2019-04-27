<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GPS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSDestLatitudeRef extends AbstractTag
{

    protected $Id = 19;

    protected $Name = 'GPSDestLatitudeRef';

    protected $FullName = 'GPS::Main';

    protected $GroupName = 'GPS';

    protected $g0 = 'EXIF';

    protected $g1 = 'GPS';

    protected $g2 = 'Location';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'GPS Dest Latitude Ref';

    protected $MaxLength = 2;

    protected $Values = array(
        'N' => array(
            'Id' => 'N',
            'Label' => 'North',
        ),
        'S' => array(
            'Id' => 'S',
            'Label' => 'South',
        ),
    );

}
