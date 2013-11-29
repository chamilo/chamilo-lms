<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBAdjLighting extends AbstractTag
{

    protected $Id = 21;

    protected $Name = 'WBAdjLighting';

    protected $FullName = 'NikonCapture::WBAdjData';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'WB Adj Lighting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Incandescent',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Daylight',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Standard Fluorescent',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'High Color Rendering Fluorescent',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Flash',
        ),
    );

}
