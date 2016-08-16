<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CodingMethods extends AbstractTag
{

    protected $Id = 403;

    protected $Name = 'CodingMethods';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Coding Methods';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Unspecified compression',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Modified Huffman',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Modified Read',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Modified MR',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'JBIG',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Baseline JPEG',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'JBIG color',
        ),
    );

}
