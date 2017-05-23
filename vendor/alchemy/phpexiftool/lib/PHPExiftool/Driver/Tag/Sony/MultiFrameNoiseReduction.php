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
class MultiFrameNoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MultiFrameNoiseReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Multi Frame Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        2 => array(
            'Id' => 16,
            'Label' => 'On',
        ),
        3 => array(
            'Id' => 255,
            'Label' => 'None',
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
            'Id' => 255,
            'Label' => 'n/a',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 16,
            'Label' => 'On',
        ),
        10 => array(
            'Id' => 255,
            'Label' => 'None',
        ),
    );

}
