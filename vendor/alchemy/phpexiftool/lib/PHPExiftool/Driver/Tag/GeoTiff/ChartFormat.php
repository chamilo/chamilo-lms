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
class ChartFormat extends AbstractTag
{

    protected $Id = 47001;

    protected $Name = 'ChartFormat';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Chart Format';

    protected $Values = array(
        47500 => array(
            'Id' => 47500,
            'Label' => 'General',
        ),
        47501 => array(
            'Id' => 47501,
            'Label' => 'Coastal',
        ),
        47502 => array(
            'Id' => 47502,
            'Label' => 'Harbor',
        ),
        47503 => array(
            'Id' => 47503,
            'Label' => 'SailingInternational',
        ),
        47504 => array(
            'Id' => 47504,
            'Label' => 'SmallCraft Route',
        ),
        47505 => array(
            'Id' => 47505,
            'Label' => 'SmallCraftArea',
        ),
        47506 => array(
            'Id' => 47506,
            'Label' => 'SmallCraftFolio',
        ),
        47507 => array(
            'Id' => 47507,
            'Label' => 'Topographic',
        ),
        47508 => array(
            'Id' => 47508,
            'Label' => 'Recreation',
        ),
        47509 => array(
            'Id' => 47509,
            'Label' => 'Index',
        ),
        47510 => array(
            'Id' => 47510,
            'Label' => 'Inset',
        ),
    );

}
