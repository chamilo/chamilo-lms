<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExposureLevelIncrements extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureLevelIncrements';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Exposure Level Increments';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1/2 Stop',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1/3 Stop',
        ),
        2 => array(
            'Id' => 0,
            'Label' => '1/3-stop set, 1/3-stop comp.',
        ),
        3 => array(
            'Id' => 1,
            'Label' => '1-stop set, 1/3-stop comp.',
        ),
        4 => array(
            'Id' => 2,
            'Label' => '1/2-stop set, 1/2-stop comp.',
        ),
        5 => array(
            'Id' => 0,
            'Label' => '1/3-stop set, 1/3-stop comp.',
        ),
        6 => array(
            'Id' => 1,
            'Label' => '1-stop set, 1/3-stop comp.',
        ),
        7 => array(
            'Id' => 2,
            'Label' => '1/2-stop set, 1/2-stop comp.',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '1/3 Stop',
        ),
        9 => array(
            'Id' => 1,
            'Label' => '1/2 Stop',
        ),
        10 => array(
            'Id' => 0,
            'Label' => '1/3 Stop',
        ),
        11 => array(
            'Id' => 1,
            'Label' => '1/2 Stop',
        ),
        12 => array(
            'Id' => 0,
            'Label' => '1/3 Stop',
        ),
        13 => array(
            'Id' => 1,
            'Label' => '1/2 Stop',
        ),
        14 => array(
            'Id' => 0,
            'Label' => '1/3 Stop',
        ),
        15 => array(
            'Id' => 1,
            'Label' => '1/2 Stop',
        ),
        16 => array(
            'Id' => 0,
            'Label' => '1/3 Stop',
        ),
        17 => array(
            'Id' => 1,
            'Label' => '1/2 Stop',
        ),
        18 => array(
            'Id' => 0,
            'Label' => '1/3 Stop',
        ),
        19 => array(
            'Id' => 1,
            'Label' => '1/2 Stop',
        ),
        20 => array(
            'Id' => 0,
            'Label' => '1/2 Stop',
        ),
        21 => array(
            'Id' => 1,
            'Label' => '1/3 Stop',
        ),
    );

    protected $Index = 'mixed';

}
