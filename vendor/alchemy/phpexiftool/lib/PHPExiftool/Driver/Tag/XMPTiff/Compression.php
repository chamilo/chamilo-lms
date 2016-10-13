<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPTiff;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Compression extends AbstractTag
{

    protected $Id = 'Compression';

    protected $Name = 'Compression';

    protected $FullName = 'XMP::tiff';

    protected $GroupName = 'XMP-tiff';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-tiff';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Compression';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Uncompressed',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'CCITT 1D',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'T4/Group 3 Fax',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'T6/Group 4 Fax',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'LZW',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'JPEG (old-style)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'JPEG',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Adobe Deflate',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'JBIG B&W',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'JBIG Color',
        ),
        99 => array(
            'Id' => 99,
            'Label' => 'JPEG',
        ),
        262 => array(
            'Id' => 262,
            'Label' => 'Kodak 262',
        ),
        32766 => array(
            'Id' => 32766,
            'Label' => 'Next',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'Sony ARW Compressed',
        ),
        32769 => array(
            'Id' => 32769,
            'Label' => 'Packed RAW',
        ),
        32770 => array(
            'Id' => 32770,
            'Label' => 'Samsung SRW Compressed',
        ),
        32771 => array(
            'Id' => 32771,
            'Label' => 'CCIRLEW',
        ),
        32772 => array(
            'Id' => 32772,
            'Label' => 'Samsung SRW Compressed 2',
        ),
        32773 => array(
            'Id' => 32773,
            'Label' => 'PackBits',
        ),
        32809 => array(
            'Id' => 32809,
            'Label' => 'Thunderscan',
        ),
        32867 => array(
            'Id' => 32867,
            'Label' => 'Kodak KDC Compressed',
        ),
        32895 => array(
            'Id' => 32895,
            'Label' => 'IT8CTPAD',
        ),
        32896 => array(
            'Id' => 32896,
            'Label' => 'IT8LW',
        ),
        32897 => array(
            'Id' => 32897,
            'Label' => 'IT8MP',
        ),
        32898 => array(
            'Id' => 32898,
            'Label' => 'IT8BL',
        ),
        32908 => array(
            'Id' => 32908,
            'Label' => 'PixarFilm',
        ),
        32909 => array(
            'Id' => 32909,
            'Label' => 'PixarLog',
        ),
        32946 => array(
            'Id' => 32946,
            'Label' => 'Deflate',
        ),
        32947 => array(
            'Id' => 32947,
            'Label' => 'DCS',
        ),
        34661 => array(
            'Id' => 34661,
            'Label' => 'JBIG',
        ),
        34676 => array(
            'Id' => 34676,
            'Label' => 'SGILog',
        ),
        34677 => array(
            'Id' => 34677,
            'Label' => 'SGILog24',
        ),
        34712 => array(
            'Id' => 34712,
            'Label' => 'JPEG 2000',
        ),
        34713 => array(
            'Id' => 34713,
            'Label' => 'Nikon NEF Compressed',
        ),
        34715 => array(
            'Id' => 34715,
            'Label' => 'JBIG2 TIFF FX',
        ),
        34718 => array(
            'Id' => 34718,
            'Label' => 'Microsoft Document Imaging (MDI) Binary Level Codec',
        ),
        34719 => array(
            'Id' => 34719,
            'Label' => 'Microsoft Document Imaging (MDI) Progressive Transform Codec',
        ),
        34720 => array(
            'Id' => 34720,
            'Label' => 'Microsoft Document Imaging (MDI) Vector',
        ),
        34892 => array(
            'Id' => 34892,
            'Label' => 'Lossy JPEG',
        ),
        65000 => array(
            'Id' => 65000,
            'Label' => 'Kodak DCR Compressed',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'Pentax PEF Compressed',
        ),
    );

}
