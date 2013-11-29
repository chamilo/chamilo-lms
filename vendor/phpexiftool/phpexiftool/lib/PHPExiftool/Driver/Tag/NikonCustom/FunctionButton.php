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
class FunctionButton extends AbstractTag
{

    protected $Id = '5.2';

    protected $Name = 'FunctionButton';

    protected $FullName = 'NikonCustom::SettingsD80';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Function Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'ISO Display',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Framing Grid',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'AF-area Mode',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Center AF Area',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'FV Lock',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Flash Off',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Matrix Metering',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'Center-weighted',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Spot Metering',
        ),
    );

}
