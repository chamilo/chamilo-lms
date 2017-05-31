<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

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

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 2,
            'Label' => 'Macro',
        ),
        1 => array(
            'Id' => 3,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        3 => array(
            'Id' => 5,
            'Label' => 'Infinity',
        ),
        4 => array(
            'Id' => 7,
            'Label' => 'Spot AF',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Macro',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Focus Lock',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Macro',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'Single-Area Auto Focus',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'Infinity',
        ),
        12 => array(
            'Id' => 6,
            'Label' => 'Multi-Area Auto Focus',
        ),
        13 => array(
            'Id' => 8,
            'Label' => 'Super Macro',
        ),
    );

}
