<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SwitchToRegisteredAFPoint extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SwitchToRegisteredAFPoint';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Switch To Registered AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Assist + AF',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Assist',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Only while pressing assist',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Switch with multi-controller',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Only while AEL is pressed',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
    );

    protected $Index = 'mixed';

}
