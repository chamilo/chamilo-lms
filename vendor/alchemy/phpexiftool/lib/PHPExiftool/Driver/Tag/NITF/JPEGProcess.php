<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NITF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class JPEGProcess extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'JPEGProcess';

    protected $FullName = 'JPEG::NITF';

    protected $GroupName = 'NITF';

    protected $g0 = 'APP6';

    protected $g1 = 'NITF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'JPEG Process';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Baseline sequential DCT, Huffman coding, 8-bit samples',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Extended sequential DCT, Huffman coding, 12-bit samples',
        ),
    );

}
