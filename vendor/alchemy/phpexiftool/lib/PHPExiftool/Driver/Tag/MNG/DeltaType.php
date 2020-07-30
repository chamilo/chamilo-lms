<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DeltaType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'DeltaType';

    protected $FullName = 'mixed';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Delta Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Absolute',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Relative',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Full Replacement',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Pixel Addition',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Alpha Addition',
        ),
        5 => array(
            'Id' => 3,
            'Label' => 'Color Addition',
        ),
        6 => array(
            'Id' => 4,
            'Label' => 'Pixel Replacement',
        ),
        7 => array(
            'Id' => 5,
            'Label' => 'Alpha Replacement',
        ),
        8 => array(
            'Id' => 6,
            'Label' => 'Color Replacement',
        ),
        9 => array(
            'Id' => 7,
            'Label' => 'No Change',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Absolute',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Relative',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Absolute',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Relative',
        ),
    );

}
