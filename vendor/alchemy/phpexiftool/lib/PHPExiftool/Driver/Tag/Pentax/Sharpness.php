<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Sharpness extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Sharpness';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Sharpness';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '-2 (soft)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '0 (normal)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '+2 (hard)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '-1 (med soft)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '+1 (med hard)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '-3 (very soft)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '+3 (very hard)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '-4 (minimum)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '+4 (maximum)',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Soft',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Hard',
        ),
    );

}
