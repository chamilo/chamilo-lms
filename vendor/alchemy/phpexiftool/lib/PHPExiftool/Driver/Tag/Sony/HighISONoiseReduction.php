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
class HighISONoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'HighISONoiseReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'High ISO Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'High',
        ),
        8 => array(
            'Id' => 16,
            'Label' => 'Low',
        ),
        9 => array(
            'Id' => 19,
            'Label' => 'Auto',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'High',
        ),
        14 => array(
            'Id' => 256,
            'Label' => 'Auto',
        ),
        15 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Low',
        ),
        17 => array(
            'Id' => 19,
            'Label' => 'Auto',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        21 => array(
            'Id' => 3,
            'Label' => 'High',
        ),
    );

}
