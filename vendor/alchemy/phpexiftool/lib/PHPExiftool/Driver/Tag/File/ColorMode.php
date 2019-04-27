<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorMode extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'ColorMode';

    protected $FullName = 'PGF::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Color Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Bitmap',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Grayscale',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Indexed',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'RGB',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'CMYK',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Multichannel',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Duotone',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Lab',
        ),
    );

}
