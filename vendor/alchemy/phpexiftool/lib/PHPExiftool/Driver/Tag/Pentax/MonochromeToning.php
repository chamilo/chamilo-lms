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
class MonochromeToning extends AbstractTag
{

    protected $Id = 116;

    protected $Name = 'MonochromeToning';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Monochrome Toning';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '-4',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '-3',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '-2',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '-1',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 0,
        ),
        5 => array(
            'Id' => 5,
            'Label' => 1,
        ),
        6 => array(
            'Id' => 6,
            'Label' => 2,
        ),
        7 => array(
            'Id' => 7,
            'Label' => 3,
        ),
        8 => array(
            'Id' => 8,
            'Label' => 4,
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'None',
        ),
    );

}
