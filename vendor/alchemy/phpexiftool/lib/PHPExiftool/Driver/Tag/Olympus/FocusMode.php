<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

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

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single AF',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Sequential shooting AF',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Continuous AF',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Multi AF',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Face detect',
        ),
        5 => array(
            'Id' => 10,
            'Label' => 'MF',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
    );

}
