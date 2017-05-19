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
class VerticalDatum extends AbstractTag
{

    protected $Id = 4098;

    protected $Name = 'VerticalDatum';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Vertical Datum';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Undefined',
        ),
        5001 => array(
            'Id' => 5001,
            'Label' => 'Airy 1830 ellipsoid',
        ),
        5002 => array(
            'Id' => 5002,
            'Label' => 'Airy Modified 1849 ellipsoid',
        ),
        5003 => array(
            'Id' => 5003,
            'Label' => 'ANS ellipsoid',
        ),
        5004 => array(
            'Id' => 5004,
            'Label' => 'Bessel 1841 ellipsoid',
        ),
        5005 => array(
            'Id' => 5005,
            'Label' => 'Bessel Modified ellipsoid',
        ),
        5006 => array(
            'Id' => 5006,
            'Label' => 'Bessel Namibia ellipsoid',
        ),
        5007 => array(
            'Id' => 5007,
            'Label' => 'Clarke 1858 ellipsoid',
        ),
        5008 => array(
            'Id' => 5008,
            'Label' => 'Clarke 1866 ellipsoid',
        ),
        5010 => array(
            'Id' => 5010,
            'Label' => 'Clarke 1880 Benoit ellipsoid',
        ),
        5011 => array(
            'Id' => 5011,
            'Label' => 'Clarke 1880 IGN ellipsoid',
        ),
        5012 => array(
            'Id' => 5012,
            'Label' => 'Clarke 1880 RGS ellipsoid',
        ),
        5013 => array(
            'Id' => 5013,
            'Label' => 'Clarke 1880 Arc ellipsoid',
        ),
        5014 => array(
            'Id' => 5014,
            'Label' => 'Clarke 1880 SGA 1922 ellipsoid',
        ),
        5015 => array(
            'Id' => 5015,
            'Label' => 'Everest 1830 1937 Adjustment ellipsoid',
        ),
        5016 => array(
            'Id' => 5016,
            'Label' => 'Everest 1830 1967 Definition ellipsoid',
        ),
        5017 => array(
            'Id' => 5017,
            'Label' => 'Everest 1830 1975 Definition ellipsoid',
        ),
        5018 => array(
            'Id' => 5018,
            'Label' => 'Everest 1830 Modified ellipsoid',
        ),
        5019 => array(
            'Id' => 5019,
            'Label' => 'GRS 1980 ellipsoid',
        ),
        5020 => array(
            'Id' => 5020,
            'Label' => 'Helmert 1906 ellipsoid',
        ),
        5021 => array(
            'Id' => 5021,
            'Label' => 'INS ellipsoid',
        ),
        5022 => array(
            'Id' => 5022,
            'Label' => 'International 1924 ellipsoid',
        ),
        5023 => array(
            'Id' => 5023,
            'Label' => 'International 1967 ellipsoid',
        ),
        5024 => array(
            'Id' => 5024,
            'Label' => 'Krassowsky 1940 ellipsoid',
        ),
        5025 => array(
            'Id' => 5025,
            'Label' => 'NWL 9D ellipsoid',
        ),
        5026 => array(
            'Id' => 5026,
            'Label' => 'NWL 10D ellipsoid',
        ),
        5027 => array(
            'Id' => 5027,
            'Label' => 'Plessis 1817 ellipsoid',
        ),
        5028 => array(
            'Id' => 5028,
            'Label' => 'Struve 1860 ellipsoid',
        ),
        5029 => array(
            'Id' => 5029,
            'Label' => 'War Office ellipsoid',
        ),
        5030 => array(
            'Id' => 5030,
            'Label' => 'WGS 84 ellipsoid',
        ),
        5031 => array(
            'Id' => 5031,
            'Label' => 'GEM 10C ellipsoid',
        ),
        5032 => array(
            'Id' => 5032,
            'Label' => 'OSU86F ellipsoid',
        ),
        5033 => array(
            'Id' => 5033,
            'Label' => 'OSU91A ellipsoid',
        ),
        5101 => array(
            'Id' => 5101,
            'Label' => 'Newlyn',
        ),
        5102 => array(
            'Id' => 5102,
            'Label' => 'North American Vertical Datum 1929',
        ),
        5103 => array(
            'Id' => 5103,
            'Label' => 'North American Vertical Datum 1988',
        ),
        5104 => array(
            'Id' => 5104,
            'Label' => 'Yellow Sea 1956',
        ),
        5105 => array(
            'Id' => 5105,
            'Label' => 'Baltic Sea',
        ),
        5106 => array(
            'Id' => 5106,
            'Label' => 'Caspian Sea',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
