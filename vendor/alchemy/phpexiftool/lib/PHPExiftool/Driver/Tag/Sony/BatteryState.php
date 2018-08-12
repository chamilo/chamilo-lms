<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BatteryState extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'BatteryState';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Battery State';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 2,
            'Label' => 'Empty',
        ),
        1 => array(
            'Id' => 3,
            'Label' => 'Very Low',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'Low',
        ),
        3 => array(
            'Id' => 5,
            'Label' => 'Sufficient',
        ),
        4 => array(
            'Id' => 6,
            'Label' => 'Full',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Empty',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Low',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'Half full',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'Almost full',
        ),
        9 => array(
            'Id' => 5,
            'Label' => 'Full',
        ),
    );

}
