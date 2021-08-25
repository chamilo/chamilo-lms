<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

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

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Weak',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Medium',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Strong',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Weak',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Strong',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'Max',
        ),
    );

}
