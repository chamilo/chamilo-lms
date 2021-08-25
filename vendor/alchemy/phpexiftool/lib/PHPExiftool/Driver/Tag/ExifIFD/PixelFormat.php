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
class PixelFormat extends AbstractTag
{

    protected $Id = 48129;

    protected $Name = 'PixelFormat';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Pixel Format';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        5 => array(
            'Id' => 5,
            'Label' => 'Black & White',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '8-bit Gray',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '16-bit BGR555',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '16-bit BGR565',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '16-bit Gray',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '24-bit BGR',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '24-bit RGB',
        ),
        14 => array(
            'Id' => 14,
            'Label' => '32-bit BGR',
        ),
        15 => array(
            'Id' => 15,
            'Label' => '32-bit BGRA',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '32-bit PBGRA',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '32-bit Gray Float',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '48-bit RGB Fixed Point',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '32-bit BGR101010',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '48-bit RGB',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '64-bit RGBA',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '64-bit PRGBA',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '96-bit RGB Fixed Point',
        ),
        25 => array(
            'Id' => 25,
            'Label' => '128-bit RGBA Float',
        ),
        26 => array(
            'Id' => 26,
            'Label' => '128-bit PRGBA Float',
        ),
        27 => array(
            'Id' => 27,
            'Label' => '128-bit RGB Float',
        ),
        28 => array(
            'Id' => 28,
            'Label' => '32-bit CMYK',
        ),
        29 => array(
            'Id' => 29,
            'Label' => '64-bit RGBA Fixed Point',
        ),
        30 => array(
            'Id' => 30,
            'Label' => '128-bit RGBA Fixed Point',
        ),
        31 => array(
            'Id' => 31,
            'Label' => '64-bit CMYK',
        ),
        32 => array(
            'Id' => 32,
            'Label' => '24-bit 3 Channels',
        ),
        33 => array(
            'Id' => 33,
            'Label' => '32-bit 4 Channels',
        ),
        34 => array(
            'Id' => 34,
            'Label' => '40-bit 5 Channels',
        ),
        35 => array(
            'Id' => 35,
            'Label' => '48-bit 6 Channels',
        ),
        36 => array(
            'Id' => 36,
            'Label' => '56-bit 7 Channels',
        ),
        37 => array(
            'Id' => 37,
            'Label' => '64-bit 8 Channels',
        ),
        38 => array(
            'Id' => 38,
            'Label' => '48-bit 3 Channels',
        ),
        39 => array(
            'Id' => 39,
            'Label' => '64-bit 4 Channels',
        ),
        40 => array(
            'Id' => 40,
            'Label' => '80-bit 5 Channels',
        ),
        41 => array(
            'Id' => 41,
            'Label' => '96-bit 6 Channels',
        ),
        42 => array(
            'Id' => 42,
            'Label' => '112-bit 7 Channels',
        ),
        43 => array(
            'Id' => 43,
            'Label' => '128-bit 8 Channels',
        ),
        44 => array(
            'Id' => 44,
            'Label' => '40-bit CMYK Alpha',
        ),
        45 => array(
            'Id' => 45,
            'Label' => '80-bit CMYK Alpha',
        ),
        46 => array(
            'Id' => 46,
            'Label' => '32-bit 3 Channels Alpha',
        ),
        47 => array(
            'Id' => 47,
            'Label' => '40-bit 4 Channels Alpha',
        ),
        48 => array(
            'Id' => 48,
            'Label' => '48-bit 5 Channels Alpha',
        ),
        49 => array(
            'Id' => 49,
            'Label' => '56-bit 6 Channels Alpha',
        ),
        50 => array(
            'Id' => 50,
            'Label' => '64-bit 7 Channels Alpha',
        ),
        51 => array(
            'Id' => 51,
            'Label' => '72-bit 8 Channels Alpha',
        ),
        52 => array(
            'Id' => 52,
            'Label' => '64-bit 3 Channels Alpha',
        ),
        53 => array(
            'Id' => 53,
            'Label' => '80-bit 4 Channels Alpha',
        ),
        54 => array(
            'Id' => 54,
            'Label' => '96-bit 5 Channels Alpha',
        ),
        55 => array(
            'Id' => 55,
            'Label' => '112-bit 6 Channels Alpha',
        ),
        56 => array(
            'Id' => 56,
            'Label' => '128-bit 7 Channels Alpha',
        ),
        57 => array(
            'Id' => 57,
            'Label' => '144-bit 8 Channels Alpha',
        ),
        58 => array(
            'Id' => 58,
            'Label' => '64-bit RGBA Half',
        ),
        59 => array(
            'Id' => 59,
            'Label' => '48-bit RGB Half',
        ),
        61 => array(
            'Id' => 61,
            'Label' => '32-bit RGBE',
        ),
        62 => array(
            'Id' => 62,
            'Label' => '16-bit Gray Half',
        ),
        63 => array(
            'Id' => 63,
            'Label' => '32-bit Gray Fixed Point',
        ),
    );

}
