<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Flash;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioEncoding extends AbstractTag
{

    protected $Id = 'Bit0-3';

    protected $Name = 'AudioEncoding';

    protected $FullName = 'Flash::Audio';

    protected $GroupName = 'Flash';

    protected $g0 = 'Flash';

    protected $g1 = 'Flash';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Audio Encoding';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'PCM-BE (uncompressed)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'ADPCM',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'MP3',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'PCM-LE (uncompressed)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Nellymoser 16kHz Mono',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Nellymoser 8kHz Mono',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Nellymoser',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'G.711 A-law logarithmic PCM',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'G.711 mu-law logarithmic PCM',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'AAC',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Speex',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'MP3 8-Khz',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Device-specific sound',
        ),
    );

}
