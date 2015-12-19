<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class VerticalAFOnButton extends AbstractTag
{

    protected $Id = '3.2';

    protected $Name = 'VerticalAFOnButton';

    protected $FullName = 'NikonCustom::SettingsD3';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Vertical AF On Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF On',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'AE/AF Lock',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'AE Lock (reset on release)',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'AE Lock (hold)',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'AF Lock Only',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'Same as AF On',
        ),
    );

}
