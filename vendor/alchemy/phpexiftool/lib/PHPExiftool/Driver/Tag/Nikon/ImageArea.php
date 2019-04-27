<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageArea extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'ImageArea';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Image Area';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'FX (36x24)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'DX (24x16)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '5:4 (30x24)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1.2x (30x20)',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'FX (36.0 x 23.9 mm)',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'DX (23.5 x 15.6 mm)',
        ),
        6 => array(
            'Id' => 2,
            'Label' => '5:4 (30.0 x 23.9 mm)',
        ),
    );

}
