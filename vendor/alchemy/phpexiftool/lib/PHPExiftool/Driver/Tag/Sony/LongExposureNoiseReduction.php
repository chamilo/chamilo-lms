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
class LongExposureNoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LongExposureNoiseReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Long Exposure Noise Reduction';

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
            'Id' => 1,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 16,
            'Label' => 'On',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'On (unused)',
        ),
        6 => array(
            'Id' => 65537,
            'Label' => 'On (dark subtracted)',
        ),
        7 => array(
            'Id' => '4294901760',
            'Label' => 'Off (65535)',
        ),
        8 => array(
            'Id' => '4294901761',
            'Label' => 'On (65535)',
        ),
        9 => array(
            'Id' => '4294967295',
            'Label' => 'n/a',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 16,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

}
