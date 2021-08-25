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
class Saturation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Saturation';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Saturation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '-2 (low)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '0 (normal)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '+2 (high)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '-1 (med low)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '+1 (med high)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '-3 (very low)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '+3 (very high)',
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
            'Id' => 65535,
            'Label' => 'None',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
    );

}
