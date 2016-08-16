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
class LensMount extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensMount';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Lens Mount';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'A-mount',
        ),
        2 => array(
            'Id' => 17,
            'Label' => 'E-mount',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'A-mount',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'E-mount',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'A-mount',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'E-mount',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'A-mount',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'E-mount',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'A-mount',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'E-mount',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'A-mount',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'E-mount',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'A-mount',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'E-mount',
        ),
    );

}
