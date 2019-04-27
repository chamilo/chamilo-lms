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
class GeogEllipsoid extends AbstractTag
{

    protected $Id = 2056;

    protected $Name = 'GeogEllipsoid';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Geog Ellipsoid';

    protected $Values = array(
        7001 => array(
            'Id' => 7001,
            'Label' => 'Airy 1830',
        ),
        7002 => array(
            'Id' => 7002,
            'Label' => 'Airy Modified 1849',
        ),
        7003 => array(
            'Id' => 7003,
            'Label' => 'Australian National Spheroid',
        ),
        7004 => array(
            'Id' => 7004,
            'Label' => 'Bessel 1841',
        ),
        7005 => array(
            'Id' => 7005,
            'Label' => 'Bessel Modified',
        ),
        7006 => array(
            'Id' => 7006,
            'Label' => 'Bessel Namibia',
        ),
        7007 => array(
            'Id' => 7007,
            'Label' => 'Clarke 1858',
        ),
        7008 => array(
            'Id' => 7008,
            'Label' => 'Clarke 1866',
        ),
        7009 => array(
            'Id' => 7009,
            'Label' => 'Clarke 1866 Michigan',
        ),
        7010 => array(
            'Id' => 7010,
            'Label' => 'Clarke 1880 Benoit',
        ),
        7011 => array(
            'Id' => 7011,
            'Label' => 'Clarke 1880 IGN',
        ),
        7012 => array(
            'Id' => 7012,
            'Label' => 'Clarke 1880 RGS',
        ),
        7013 => array(
            'Id' => 7013,
            'Label' => 'Clarke 1880 Arc',
        ),
        7014 => array(
            'Id' => 7014,
            'Label' => 'Clarke 1880 SGA 1922',
        ),
        7015 => array(
            'Id' => 7015,
            'Label' => 'Everest 1830 1937 Adjustment',
        ),
        7016 => array(
            'Id' => 7016,
            'Label' => 'Everest 1830 1967 Definition',
        ),
        7017 => array(
            'Id' => 7017,
            'Label' => 'Everest 1830 1975 Definition',
        ),
        7018 => array(
            'Id' => 7018,
            'Label' => 'Everest 1830 Modified',
        ),
        7019 => array(
            'Id' => 7019,
            'Label' => 'GRS 1980',
        ),
        7020 => array(
            'Id' => 7020,
            'Label' => 'Helmert 1906',
        ),
        7021 => array(
            'Id' => 7021,
            'Label' => 'Indonesian National Spheroid',
        ),
        7022 => array(
            'Id' => 7022,
            'Label' => 'International 1924',
        ),
        7023 => array(
            'Id' => 7023,
            'Label' => 'International 1967',
        ),
        7024 => array(
            'Id' => 7024,
            'Label' => 'Krassowsky 1940',
        ),
        7025 => array(
            'Id' => 7025,
            'Label' => 'NWL 9D',
        ),
        7026 => array(
            'Id' => 7026,
            'Label' => 'NWL 10D',
        ),
        7027 => array(
            'Id' => 7027,
            'Label' => 'Plessis 1817',
        ),
        7028 => array(
            'Id' => 7028,
            'Label' => 'Struve 1860',
        ),
        7029 => array(
            'Id' => 7029,
            'Label' => 'War Office',
        ),
        7030 => array(
            'Id' => 7030,
            'Label' => 'WGS 84',
        ),
        7031 => array(
            'Id' => 7031,
            'Label' => 'GEM 10C',
        ),
        7032 => array(
            'Id' => 7032,
            'Label' => 'OSU86F',
        ),
        7033 => array(
            'Id' => 7033,
            'Label' => 'OSU91A',
        ),
        7034 => array(
            'Id' => 7034,
            'Label' => 'Clarke 1880',
        ),
        7035 => array(
            'Id' => 7035,
            'Label' => 'Sphere',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
