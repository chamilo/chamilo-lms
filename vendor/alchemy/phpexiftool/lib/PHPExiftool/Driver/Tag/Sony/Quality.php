<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Quality';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'RAW',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'CRAW',
        ),
        2 => array(
            'Id' => 16,
            'Label' => 'Extra Fine',
        ),
        3 => array(
            'Id' => 32,
            'Label' => 'Fine',
        ),
        4 => array(
            'Id' => 34,
            'Label' => 'RAW + JPEG',
        ),
        5 => array(
            'Id' => 35,
            'Label' => 'CRAW + JPEG',
        ),
        6 => array(
            'Id' => 48,
            'Label' => 'Standard',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'RAW',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'RAW + JPEG',
        ),
        9 => array(
            'Id' => 6,
            'Label' => 'Fine',
        ),
        10 => array(
            'Id' => 7,
            'Label' => 'Standard',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'RAW',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Super Fine',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Fine',
        ),
        14 => array(
            'Id' => 3,
            'Label' => 'Standard',
        ),
        15 => array(
            'Id' => 4,
            'Label' => 'Economy',
        ),
        16 => array(
            'Id' => 5,
            'Label' => 'Extra Fine',
        ),
        17 => array(
            'Id' => 6,
            'Label' => 'RAW + JPEG',
        ),
        18 => array(
            'Id' => 7,
            'Label' => 'Compressed RAW',
        ),
        19 => array(
            'Id' => 8,
            'Label' => 'Compressed RAW + JPEG',
        ),
        20 => array(
            'Id' => '4294967295',
            'Label' => 'n/a',
        ),
    );

}
