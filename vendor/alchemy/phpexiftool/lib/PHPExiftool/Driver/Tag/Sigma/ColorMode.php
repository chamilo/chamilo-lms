<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sigma;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorMode extends AbstractTag
{

    protected $Id = 44;

    protected $Name = 'ColorMode';

    protected $FullName = 'Sigma::Main';

    protected $GroupName = 'Sigma';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sigma';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Color Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Sepia',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'B&W',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Standard',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Vivid',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Neutral',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Portrait',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Landscape',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'FOV Classic Blue',
        ),
    );

}
