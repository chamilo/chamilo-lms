<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'MF',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'AF-S',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        4 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        5 => array(
            'Id' => 4,
            'Label' => 'AF-A',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'AF-S',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'AF-A',
        ),
        9 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
        10 => array(
            'Id' => 6,
            'Label' => 'DMF',
        ),
    );

}
