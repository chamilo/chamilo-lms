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
class ChartSoundingDatum extends AbstractTag
{

    protected $Id = 47008;

    protected $Name = 'ChartSoundingDatum';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Chart Sounding Datum';

    protected $Values = array(
        47600 => array(
            'Id' => 47600,
            'Label' => 'Equatorial Spring Low Water',
        ),
        47601 => array(
            'Id' => 47601,
            'Label' => 'Indian Spring Low Water',
        ),
        47602 => array(
            'Id' => 47602,
            'Label' => 'Lowest Astronomical Tide',
        ),
        47603 => array(
            'Id' => 47603,
            'Label' => 'Lowest Low Water',
        ),
        47604 => array(
            'Id' => 47604,
            'Label' => 'Lowest Normal Low Water',
        ),
        47605 => array(
            'Id' => 47605,
            'Label' => 'Mean Higher High Water',
        ),
        47606 => array(
            'Id' => 47606,
            'Label' => 'Mean High Water',
        ),
        47607 => array(
            'Id' => 47607,
            'Label' => 'Mean High Water Springs',
        ),
        47608 => array(
            'Id' => 47608,
            'Label' => 'Mean Lower Low Water',
        ),
        47609 => array(
            'Id' => 47609,
            'Label' => 'Mean Lower Low Water Springs',
        ),
        47610 => array(
            'Id' => 47610,
            'Label' => 'Mean Low Water',
        ),
        47611 => array(
            'Id' => 47611,
            'Label' => 'Mean Sea Level',
        ),
        47612 => array(
            'Id' => 47612,
            'Label' => 'Tropic Higher High Water',
        ),
        47613 => array(
            'Id' => 47613,
            'Label' => 'Tropic Lower Low Water',
        ),
    );

}
