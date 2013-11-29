<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AppleStoreCountry extends AbstractTag
{

    protected $Id = 'sfID';

    protected $Name = 'AppleStoreCountry';

    protected $FullName = 'QuickTime::ItemList';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Apple Store Country';

    protected $Values = array(
        143441 => array(
            'Id' => 143441,
            'Label' => 'United States',
        ),
        143442 => array(
            'Id' => 143442,
            'Label' => 'France',
        ),
        143443 => array(
            'Id' => 143443,
            'Label' => 'Germany',
        ),
        143444 => array(
            'Id' => 143444,
            'Label' => 'United Kingdom',
        ),
        143445 => array(
            'Id' => 143445,
            'Label' => 'Austria',
        ),
        143446 => array(
            'Id' => 143446,
            'Label' => 'Belgium',
        ),
        143447 => array(
            'Id' => 143447,
            'Label' => 'Finland',
        ),
        143448 => array(
            'Id' => 143448,
            'Label' => 'Greece',
        ),
        143449 => array(
            'Id' => 143449,
            'Label' => 'Ireland',
        ),
        143450 => array(
            'Id' => 143450,
            'Label' => 'Italy',
        ),
        143451 => array(
            'Id' => 143451,
            'Label' => 'Luxembourg',
        ),
        143452 => array(
            'Id' => 143452,
            'Label' => 'Netherlands',
        ),
        143453 => array(
            'Id' => 143453,
            'Label' => 'Portugal',
        ),
        143454 => array(
            'Id' => 143454,
            'Label' => 'Spain',
        ),
        143455 => array(
            'Id' => 143455,
            'Label' => 'Canada',
        ),
        143456 => array(
            'Id' => 143456,
            'Label' => 'Sweden',
        ),
        143457 => array(
            'Id' => 143457,
            'Label' => 'Norway',
        ),
        143458 => array(
            'Id' => 143458,
            'Label' => 'Denmark',
        ),
        143459 => array(
            'Id' => 143459,
            'Label' => 'Switzerland',
        ),
        143460 => array(
            'Id' => 143460,
            'Label' => 'Australia',
        ),
        143461 => array(
            'Id' => 143461,
            'Label' => 'New Zealand',
        ),
        143462 => array(
            'Id' => 143462,
            'Label' => 'Japan',
        ),
    );

}
