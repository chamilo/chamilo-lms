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
class EncodingProcess extends AbstractTag
{

    protected $Id = 'EncodingProcess';

    protected $Name = 'EncodingProcess';

    protected $FullName = 'JPEG::SOF';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Encoding Process';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Baseline DCT, Huffman coding',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Extended sequential DCT, Huffman coding',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Progressive DCT, Huffman coding',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Lossless, Huffman coding',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Sequential DCT, differential Huffman coding',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Progressive DCT, differential Huffman coding',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Lossless, Differential Huffman coding',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Extended sequential DCT, arithmetic coding',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Progressive DCT, arithmetic coding',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Lossless, arithmetic coding',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Sequential DCT, differential arithmetic coding',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Progressive DCT, differential arithmetic coding',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Lossless, differential arithmetic coding',
        ),
    );

}
