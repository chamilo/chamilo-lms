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
class GPSLongitudeRef extends AbstractTag
{

    protected $Id = 'GPSLongitudeRef';

    protected $Name = 'GPSLongitudeRef';

    protected $FullName = 'Composite';

    protected $GroupName = 'Composite';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Longitude Ref';

    protected $Values = array(
        'E' => array(
            'Id' => 'E',
            'Label' => 'East',
        ),
        'W' => array(
            'Id' => 'W',
            'Label' => 'West',
        ),
    );

}
