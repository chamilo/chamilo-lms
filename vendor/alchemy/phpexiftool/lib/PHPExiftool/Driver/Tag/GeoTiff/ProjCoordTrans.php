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
class ProjCoordTrans extends AbstractTag
{

    protected $Id = 3075;

    protected $Name = 'ProjCoordTrans';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Proj Coord Trans';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Transverse Mercator',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Transverse Mercator Modified Alaska',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Oblique Mercator',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Oblique Mercator Laborde',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Oblique Mercator Rosenmund',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Oblique Mercator Spherical',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Mercator',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Lambert Conf Conic 2SP',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Lambert Conf Conic 1SP',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Lambert Azim Equal Area',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Albers Equal Area',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Azimuthal Equidistant',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Equidistant Conic',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Stereographic',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Polar Stereographic',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Oblique Stereographic',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Equirectangular',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Cassini Soldner',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Gnomonic',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Miller Cylindrical',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Orthographic',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Polyconic',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Robinson',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Sinusoidal',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'VanDerGrinten',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'New Zealand Map Grid',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Transverse Mercator South Orientated',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Cylindrical Equal Area',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
