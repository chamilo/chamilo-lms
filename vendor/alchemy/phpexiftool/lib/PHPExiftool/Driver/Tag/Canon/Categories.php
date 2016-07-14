<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Categories extends AbstractTag
{

    protected $Id = 35;

    protected $Name = 'Categories';

    protected $FullName = 'Canon::Main';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Categories';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'People',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Events',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'User 1',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'User 2',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'User 3',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'To Do',
        ),
    );

}
