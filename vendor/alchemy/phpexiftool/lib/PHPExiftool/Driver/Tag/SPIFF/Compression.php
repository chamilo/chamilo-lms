<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SPIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Compression extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'Compression';

    protected $FullName = 'JPEG::SPIFF';

    protected $GroupName = 'SPIFF';

    protected $g0 = 'APP8';

    protected $g1 = 'SPIFF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Compression';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Uncompressed, interleaved, 8 bits per sample',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Modified Huffman',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Modified READ',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Modified Modified READ',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'JBIG',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'JPEG',
        ),
    );

}
