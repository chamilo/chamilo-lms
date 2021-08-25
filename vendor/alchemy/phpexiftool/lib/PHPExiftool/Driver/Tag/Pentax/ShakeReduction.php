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
class ShakeReduction extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'ShakeReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Shake Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'Off (4)',
        ),
        3 => array(
            'Id' => 5,
            'Label' => 'On but Disabled',
        ),
        4 => array(
            'Id' => 6,
            'Label' => 'On (Video)',
        ),
        5 => array(
            'Id' => 7,
            'Label' => 'On (7)',
        ),
        6 => array(
            'Id' => 15,
            'Label' => 'On (15)',
        ),
        7 => array(
            'Id' => 39,
            'Label' => 'On (mode 2)',
        ),
        8 => array(
            'Id' => 135,
            'Label' => 'On (135)',
        ),
        9 => array(
            'Id' => 167,
            'Label' => 'On (mode 1)',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'Off (AA simulation off)',
        ),
        13 => array(
            'Id' => 5,
            'Label' => 'On but Disabled',
        ),
        14 => array(
            'Id' => 6,
            'Label' => 'On (Video)',
        ),
        15 => array(
            'Id' => 7,
            'Label' => 'On (AA simulation off)',
        ),
        16 => array(
            'Id' => 12,
            'Label' => 'Off (AA simulation type 1)',
        ),
        17 => array(
            'Id' => 15,
            'Label' => 'On (AA simulation type 1)',
        ),
        18 => array(
            'Id' => 20,
            'Label' => 'Off (AA simulation type 2)',
        ),
        19 => array(
            'Id' => 23,
            'Label' => 'On (AA simulation type 2)',
        ),
    );

}
