<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPCrs;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalance extends AbstractTag
{

    protected $Id = 'WhiteBalance';

    protected $Name = 'WhiteBalance';

    protected $FullName = 'XMP::crs';

    protected $GroupName = 'XMP-crs';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-crs';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'White Balance';

    protected $flag_Avoid = true;

    protected $Values = array(
        'As Shot' => array(
            'Id' => 'As Shot',
            'Label' => 'As Shot',
        ),
        'Auto' => array(
            'Id' => 'Auto',
            'Label' => 'Auto',
        ),
        'Cloudy' => array(
            'Id' => 'Cloudy',
            'Label' => 'Cloudy',
        ),
        'Custom' => array(
            'Id' => 'Custom',
            'Label' => 'Custom',
        ),
        'Daylight' => array(
            'Id' => 'Daylight',
            'Label' => 'Daylight',
        ),
        'Flash' => array(
            'Id' => 'Flash',
            'Label' => 'Flash',
        ),
        'Fluorescent' => array(
            'Id' => 'Fluorescent',
            'Label' => 'Fluorescent',
        ),
        'Shade' => array(
            'Id' => 'Shade',
            'Label' => 'Shade',
        ),
        'Tungsten' => array(
            'Id' => 'Tungsten',
            'Label' => 'Tungsten',
        ),
    );

}
