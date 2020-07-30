<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ObjectPreviewFileFormat extends AbstractTag
{

    protected $Id = 200;

    protected $Name = 'ObjectPreviewFileFormat';

    protected $FullName = 'IPTC::ApplicationRecord';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Object Preview File Format';

    protected $local_g2 = 'Image';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No ObjectData',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'IPTC-NAA Digital Newsphoto Parameter Record',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'IPTC7901 Recommended Message Format',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Tagged Image File Format (Adobe/Aldus Image data)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Illustrator (Adobe Graphics data)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AppleSingle (Apple Computer Inc)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'NAA 89-3 (ANPA 1312)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'MacBinary II',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'IPTC Unstructured Character Oriented File Format (UCOFF)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'United Press International ANPA 1312 variant',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'United Press International Down-Load Message',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'JPEG File Interchange (JFIF)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Photo-CD Image-Pac (Eastman Kodak)',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Bit Mapped Graphics File [.BMP] (Microsoft)',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Digital Audio File [.WAV] (Microsoft & Creative Labs)',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Audio plus Moving Video [.AVI] (Microsoft)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'PC DOS/Windows Executable Files [.COM][.EXE]',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Compressed Binary File [.ZIP] (PKWare Inc)',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Audio Interchange File Format AIFF (Apple Computer Inc)',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'RIFF Wave (Microsoft Corporation)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Freehand (Macromedia/Aldus)',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Hypertext Markup Language [.HTML] (The Internet Society)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'MPEG 2 Audio Layer 2 (Musicom), ISO/IEC',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'MPEG 2 Audio Layer 3, ISO/IEC',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Portable Document File [.PDF] Adobe',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'News Industry Text Format (NITF)',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Tape Archive [.TAR]',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Tidningarnas Telegrambyra NITF version (TTNITF DTD)',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Ritzaus Bureau NITF version (RBNITF DTD)',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Corel Draw [.CDR]',
        ),
    );

}
