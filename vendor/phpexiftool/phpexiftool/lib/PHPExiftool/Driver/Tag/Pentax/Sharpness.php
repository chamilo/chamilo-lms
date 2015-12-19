<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
            'Label' => 'Soft',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Hard',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Med Soft',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Med Hard',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Very Soft',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Very Hard',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '-4',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '+4',
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
