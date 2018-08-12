<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GeoTiff;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GTModelType extends AbstractTag
{

    protected $Id = 1024;

    protected $Name = 'GTModelType';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GT Model Type';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Projected',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Geographic',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Geocentric',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
