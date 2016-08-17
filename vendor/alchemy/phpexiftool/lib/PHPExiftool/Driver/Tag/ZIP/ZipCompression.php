<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ZipCompression extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'ZipCompression';

    protected $FullName = 'ZIP::Main';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Zip Compression';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Shrunk',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Reduced with compression factor 1',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Reduced with compression factor 2',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Reduced with compression factor 3',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Reduced with compression factor 4',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Imploded',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Tokenized',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Deflated',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Enhanced Deflate using Deflate64(tm)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Imploded (old IBM TERSE)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'BZIP2',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'LZMA (EFS)',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'IBM TERSE (new)',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'IBM LZ77 z Architecture (PFS)',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'JPEG recompressed',
        ),
        97 => array(
            'Id' => 97,
            'Label' => 'WavPack compressed',
        ),
        98 => array(
            'Id' => 98,
            'Label' => 'PPMd version I, Rev 1',
        ),
    );

}
