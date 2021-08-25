<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusMode extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'FocusMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Manual',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Auto, Focus button',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Auto, Continuous',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'AF-S',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AF-C',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'AF-F',
        ),
    );

}
