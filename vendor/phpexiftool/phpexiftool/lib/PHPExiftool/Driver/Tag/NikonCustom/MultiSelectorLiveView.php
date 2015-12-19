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
class MultiSelectorLiveView extends AbstractTag
{

    protected $Id = '4.3';

    protected $Name = 'MultiSelectorLiveView';

    protected $FullName = 'NikonCustom::SettingsD3';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Multi Selector Live View';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Zoom On/Off',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Start Movie Recording',
        ),
        192 => array(
            'Id' => 192,
            'Label' => 'Not Used',
        ),
    );

}
