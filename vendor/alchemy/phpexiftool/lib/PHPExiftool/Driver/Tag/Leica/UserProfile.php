<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class UserProfile extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'UserProfile';

    protected $FullName = 'mixed';

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'User Profile';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'User Profile 1',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'User Profile 2',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'User Profile 3',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'User Profile 0 (Dynamic)',
        ),
    );

}
