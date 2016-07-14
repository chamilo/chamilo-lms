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
class MovieAELockButtonAssignment extends AbstractTag
{

    protected $Id = '40.1';

    protected $Name = 'MovieAELockButtonAssignment';

    protected $FullName = 'NikonCustom::SettingsD810';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Movie AE Lock Button Assignment';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AE/AF Lock',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'AE Lock Only',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AE Lock (hold)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'AF Lock Only',
        ),
    );

}
