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
class FocusModeSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusModeSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Focus Mode Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'DMF',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'AF-A',
        ),
        9 => array(
            'Id' => 17,
            'Label' => 'AF-S',
        ),
        10 => array(
            'Id' => 18,
            'Label' => 'AF-C',
        ),
        11 => array(
            'Id' => 19,
            'Label' => 'AF-A',
        ),
        12 => array(
            'Id' => 32,
            'Label' => 'Manual',
        ),
        13 => array(
            'Id' => 48,
            'Label' => 'DMF',
        ),
    );

}
