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
class MultiSelectorLiveView extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MultiSelectorLiveView';

    protected $FullName = 'mixed';

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
        1 => array(
            'Id' => 64,
            'Label' => 'Zoom On/Off',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Start Movie Recording',
        ),
        3 => array(
            'Id' => 192,
            'Label' => 'Not Used',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Reset',
        ),
        5 => array(
            'Id' => 64,
            'Label' => 'Zoom',
        ),
        6 => array(
            'Id' => 192,
            'Label' => 'Not Used',
        ),
    );

}
