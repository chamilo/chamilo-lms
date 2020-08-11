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
class VibrationReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'VibrationReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Vibration Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On (1)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On (2)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'On (3)',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        7 => array(
            'Id' => 12,
            'Label' => 'Off',
        ),
        8 => array(
            'Id' => 15,
            'Label' => 'On',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 8,
            'Label' => 'On',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        12 => array(
            'Id' => 24,
            'Label' => 'On',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
    );

}
