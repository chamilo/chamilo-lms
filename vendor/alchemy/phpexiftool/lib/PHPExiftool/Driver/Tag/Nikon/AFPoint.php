<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPoint extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'AFPoint';

    protected $FullName = 'Nikon::AFInfo';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Center',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Top',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Mid-left',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Mid-right',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Upper-left',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Upper-right',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Lower-left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Lower-right',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Far Left',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Far Right',
        ),
    );

}
