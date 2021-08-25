<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InitialZoomSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'InitialZoomSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Initial Zoom Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'High Magnification',
        ),
        1 => array(
            'Id' => 4,
            'Label' => 'Medium Magnification',
        ),
        2 => array(
            'Id' => 8,
            'Label' => 'Low Magnification',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Low Magnification',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Medium Magnification',
        ),
        5 => array(
            'Id' => 8,
            'Label' => 'High Magnification',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Low Magnification',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'Medium Magnification',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'High Magnification',
        ),
    );

    protected $Index = 'mixed';

}
