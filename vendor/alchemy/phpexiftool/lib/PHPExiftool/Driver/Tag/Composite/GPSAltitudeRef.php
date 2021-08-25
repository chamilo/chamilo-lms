<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Composite;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSAltitudeRef extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'GPSAltitudeRef';

    protected $FullName = 'Composite';

    protected $GroupName = 'Composite';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Altitude Ref';

    protected $local_g2 = 'Location';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Above Sea Level',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Below Sea Level',
        ),
    );

}
