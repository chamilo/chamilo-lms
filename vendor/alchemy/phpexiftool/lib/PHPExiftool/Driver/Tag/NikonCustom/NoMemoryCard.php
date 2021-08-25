<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NoMemoryCard extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'NoMemoryCard';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'No Memory Card';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        1 => array(
            'Id' => 128,
            'Label' => 'Enable Release',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        3 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        5 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        7 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        9 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        11 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Enable Release',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        15 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        17 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        19 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'Release Locked',
        ),
        21 => array(
            'Id' => 32,
            'Label' => 'Enable Release',
        ),
    );

}
