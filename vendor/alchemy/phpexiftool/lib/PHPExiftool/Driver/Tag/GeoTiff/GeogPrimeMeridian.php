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
class GeogPrimeMeridian extends AbstractTag
{

    protected $Id = 2051;

    protected $Name = 'GeogPrimeMeridian';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Geog Prime Meridian';

    protected $Values = array(
        8901 => array(
            'Id' => 8901,
            'Label' => 'Greenwich',
        ),
        8902 => array(
            'Id' => 8902,
            'Label' => 'Lisbon',
        ),
        8903 => array(
            'Id' => 8903,
            'Label' => 'Paris',
        ),
        8904 => array(
            'Id' => 8904,
            'Label' => 'Bogota',
        ),
        8905 => array(
            'Id' => 8905,
            'Label' => 'Madrid',
        ),
        8906 => array(
            'Id' => 8906,
            'Label' => 'Rome',
        ),
        8907 => array(
            'Id' => 8907,
            'Label' => 'Bern',
        ),
        8908 => array(
            'Id' => 8908,
            'Label' => 'Jakarta',
        ),
        8909 => array(
            'Id' => 8909,
            'Label' => 'Ferro',
        ),
        8910 => array(
            'Id' => 8910,
            'Label' => 'Brussels',
        ),
        8911 => array(
            'Id' => 8911,
            'Label' => 'Stockholm',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
