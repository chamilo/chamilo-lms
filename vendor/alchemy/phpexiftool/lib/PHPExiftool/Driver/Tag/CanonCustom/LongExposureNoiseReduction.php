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
class LongExposureNoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LongExposureNoiseReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

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
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

}
