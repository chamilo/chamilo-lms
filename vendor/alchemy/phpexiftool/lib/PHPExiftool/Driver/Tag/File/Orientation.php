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
class Orientation extends AbstractTag
{

    protected $Id = 768;

    protected $Name = 'Orientation';

    protected $FullName = 'DPX::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Orientation';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Horizontal (normal)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Mirror vertical',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Mirror horizontal',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Rotate 180',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Mirror horizontal and rotate 270 CW',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Rotate 90 CW',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Rotate 270 CW',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Mirror horizontal and rotate 90 CW',
        ),
    );

}
