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
class MBD10Batteries extends AbstractTag
{

    protected $Id = '12.6';

    protected $Name = 'MB-D10Batteries';

    protected $FullName = 'NikonCustom::SettingsD3';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'MB-D10 Batteries';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'LR6 (AA alkaline)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'HR6 (AA Ni-MH)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'FR6 (AA lithium)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'ZR6 (AA Ni-Mn)',
        ),
    );

}
