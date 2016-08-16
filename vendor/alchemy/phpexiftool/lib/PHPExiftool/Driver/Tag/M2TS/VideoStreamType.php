<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\M2TS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VideoStreamType extends AbstractTag
{

    protected $Id = 'VideoStreamType';

    protected $Name = 'VideoStreamType';

    protected $FullName = 'M2TS::Main';

    protected $GroupName = 'M2TS';

    protected $g0 = 'M2TS';

    protected $g1 = 'M2TS';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Video Stream Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Reserved',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'MPEG-1 Video',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'MPEG-2 Video',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'MPEG-1 Audio',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'MPEG-2 Audio',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'ISO 13818-1 private sections',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'ISO 13818-1 PES private data',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'ISO 13522 MHEG',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'ISO 13818-1 DSM-CC',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'ISO 13818-1 auxiliary',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'ISO 13818-6 multi-protocol encap',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'ISO 13818-6 DSM-CC U-N msgs',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'ISO 13818-6 stream descriptors',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'ISO 13818-6 sections',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'ISO 13818-1 auxiliary',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'MPEG-2 AAC Audio',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'MPEG-4 Video',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'MPEG-4 LATM AAC Audio',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'MPEG-4 generic',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'ISO 14496-1 SL-packetized',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'ISO 13818-6 Synchronized Download Protocol',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'H.264 Video',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'DigiCipher II Video',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'A52/AC-3 Audio',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'HDMV DTS Audio',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'LPCM Audio',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'SDDS Audio',
        ),
        133 => array(
            'Id' => 133,
            'Label' => 'ATSC Program ID',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'DTS-HD Audio',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'E-AC-3 Audio',
        ),
        138 => array(
            'Id' => 138,
            'Label' => 'DTS Audio',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'A52b/AC-3 Audio',
        ),
        146 => array(
            'Id' => 146,
            'Label' => 'DVD_SPU vls Subtitle',
        ),
        148 => array(
            'Id' => 148,
            'Label' => 'SDDS Audio',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'MSCODEC Video',
        ),
        234 => array(
            'Id' => 234,
            'Label' => 'Private ES (VC-1)',
        ),
    );

}
