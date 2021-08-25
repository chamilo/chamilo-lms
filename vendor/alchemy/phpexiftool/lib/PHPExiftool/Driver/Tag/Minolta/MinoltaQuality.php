<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MinoltaQuality extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MinoltaQuality';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Minolta Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Raw',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Super Fine',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Fine',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Standard',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Economy',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Extra Fine',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'RAW',
        ),
        7 => array(
            'Id' => 16,
            'Label' => 'Fine',
        ),
        8 => array(
            'Id' => 32,
            'Label' => 'Normal',
        ),
        9 => array(
            'Id' => 34,
            'Label' => 'RAW+JPEG',
        ),
        10 => array(
            'Id' => 48,
            'Label' => 'Economy',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'RAW',
        ),
        12 => array(
            'Id' => 16,
            'Label' => 'Fine',
        ),
        13 => array(
            'Id' => 32,
            'Label' => 'Normal',
        ),
        14 => array(
            'Id' => 34,
            'Label' => 'RAW+JPEG',
        ),
        15 => array(
            'Id' => 48,
            'Label' => 'Economy',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Raw',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'Super Fine',
        ),
        18 => array(
            'Id' => 2,
            'Label' => 'Fine',
        ),
        19 => array(
            'Id' => 3,
            'Label' => 'Standard',
        ),
        20 => array(
            'Id' => 4,
            'Label' => 'Economy',
        ),
        21 => array(
            'Id' => 5,
            'Label' => 'Extra fine',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Raw',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'Super Fine',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 'Fine',
        ),
        25 => array(
            'Id' => 3,
            'Label' => 'Standard',
        ),
        26 => array(
            'Id' => 4,
            'Label' => 'Economy',
        ),
        27 => array(
            'Id' => 5,
            'Label' => 'Extra fine',
        ),
    );

}
