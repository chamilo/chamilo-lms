<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'NoiseReduction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Noise Reduction',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Noise Filter',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Noise Filter (ISO Boost)',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

}
