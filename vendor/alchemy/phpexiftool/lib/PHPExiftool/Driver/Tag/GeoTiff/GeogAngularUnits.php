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
class GeogAngularUnits extends AbstractTag
{

    protected $Id = 2054;

    protected $Name = 'GeogAngularUnits';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Geog Angular Units';

    protected $Values = array(
        9001 => array(
            'Id' => 9001,
            'Label' => 'Linear Meter',
        ),
        9002 => array(
            'Id' => 9002,
            'Label' => 'Linear Foot',
        ),
        9003 => array(
            'Id' => 9003,
            'Label' => 'Linear Foot US Survey',
        ),
        9004 => array(
            'Id' => 9004,
            'Label' => 'Linear Foot Modified American',
        ),
        9005 => array(
            'Id' => 9005,
            'Label' => 'Linear Foot Clarke',
        ),
        9006 => array(
            'Id' => 9006,
            'Label' => 'Linear Foot Indian',
        ),
        9007 => array(
            'Id' => 9007,
            'Label' => 'Linear Link',
        ),
        9008 => array(
            'Id' => 9008,
            'Label' => 'Linear Link Benoit',
        ),
        9009 => array(
            'Id' => 9009,
            'Label' => 'Linear Link Sears',
        ),
        9010 => array(
            'Id' => 9010,
            'Label' => 'Linear Chain Benoit',
        ),
        9011 => array(
            'Id' => 9011,
            'Label' => 'Linear Chain Sears',
        ),
        9012 => array(
            'Id' => 9012,
            'Label' => 'Linear Yard Sears',
        ),
        9013 => array(
            'Id' => 9013,
            'Label' => 'Linear Yard Indian',
        ),
        9014 => array(
            'Id' => 9014,
            'Label' => 'Linear Fathom',
        ),
        9015 => array(
            'Id' => 9015,
            'Label' => 'Linear Mile International Nautical',
        ),
        9101 => array(
            'Id' => 9101,
            'Label' => 'Angular Radian',
        ),
        9102 => array(
            'Id' => 9102,
            'Label' => 'Angular Degree',
        ),
        9103 => array(
            'Id' => 9103,
            'Label' => 'Angular Arc Minute',
        ),
        9104 => array(
            'Id' => 9104,
            'Label' => 'Angular Arc Second',
        ),
        9105 => array(
            'Id' => 9105,
            'Label' => 'Angular Grad',
        ),
        9106 => array(
            'Id' => 9106,
            'Label' => 'Angular Gon',
        ),
        9107 => array(
            'Id' => 9107,
            'Label' => 'Angular DMS',
        ),
        9108 => array(
            'Id' => 9108,
            'Label' => 'Angular DMS Hemisphere',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
