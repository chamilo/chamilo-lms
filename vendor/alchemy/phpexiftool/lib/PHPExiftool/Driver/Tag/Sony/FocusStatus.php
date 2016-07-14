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
class FocusStatus extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusStatus';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Focus Status';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Manual - Not confirmed (0)',
        ),
        1 => array(
            'Id' => 4,
            'Label' => 'Manual - Not confirmed (4)',
        ),
        2 => array(
            'Id' => 16,
            'Label' => 'AF-C - Confirmed',
        ),
        3 => array(
            'Id' => 24,
            'Label' => 'AF-C - Not Confirmed',
        ),
        4 => array(
            'Id' => 64,
            'Label' => 'AF-S - Confirmed',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Not confirmed',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Confirmed',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Failed',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'Not confirmed, Tracking',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Not confirmed',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Confirmed',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Failed',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'Not confirmed, Tracking',
        ),
    );

}
