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
class LimitAFAreaModeSelection extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LimitAFAreaModeSelection';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Limit AF Area Mode Selection';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Restrictions',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Auto-area',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Group-area',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '3D-tracking',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Dynamic area (51 points)',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Dynamic area (21 points)',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Dynamic area (9 points)',
        ),
    );

}
