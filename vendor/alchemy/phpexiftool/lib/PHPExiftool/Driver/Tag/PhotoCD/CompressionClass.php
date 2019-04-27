<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoCD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CompressionClass extends AbstractTag
{

    protected $Id = '1538.3';

    protected $Name = 'CompressionClass';

    protected $FullName = 'PhotoCD::Main';

    protected $GroupName = 'PhotoCD';

    protected $g0 = 'PhotoCD';

    protected $g1 = 'PhotoCD';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Compression Class';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Class 1 - 35mm film; Pictoral hard copy',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Class 2 - Large format film',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Class 3 - Text and graphics, high resolution',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Class 4 - Text and graphics, high dynamic range',
        ),
    );

}
