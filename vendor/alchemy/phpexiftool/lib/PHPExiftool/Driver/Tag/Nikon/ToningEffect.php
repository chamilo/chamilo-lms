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
class ToningEffect extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ToningEffect';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Toning Effect';

    protected $flag_Permanent = true;

    protected $Values = array(
        128 => array(
            'Id' => 128,
            'Label' => 'B&W',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Sepia',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Cyanotype',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Red',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'Yellow',
        ),
        133 => array(
            'Id' => 133,
            'Label' => 'Green',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'Blue-green',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'Blue',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Purple-blue',
        ),
        137 => array(
            'Id' => 137,
            'Label' => 'Red-purple',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'n/a',
        ),
    );

}
