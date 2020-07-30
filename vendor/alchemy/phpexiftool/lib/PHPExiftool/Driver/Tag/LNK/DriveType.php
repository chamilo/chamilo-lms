<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\LNK;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DriveType extends AbstractTag
{

    protected $Id = 'DriveType';

    protected $Name = 'DriveType';

    protected $FullName = 'LNK::LinkInfo';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Drive Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Invalid Root Path',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Removable Media',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Fixed Disk',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Remote Drive',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'CD-ROM',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Ram Disk',
        ),
    );

}
