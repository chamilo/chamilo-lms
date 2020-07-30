<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPEG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SampleRate extends AbstractTag
{

    protected $Id = 'Bit20-21';

    protected $Name = 'SampleRate';

    protected $FullName = 'MPEG::Audio';

    protected $GroupName = 'MPEG';

    protected $g0 = 'MPEG';

    protected $g1 = 'MPEG';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Sample Rate';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 44100,
        ),
        1 => array(
            'Id' => 1,
            'Label' => 48000,
        ),
        2 => array(
            'Id' => 2,
            'Label' => 32000,
        ),
        3 => array(
            'Id' => 0,
            'Label' => 22050,
        ),
        4 => array(
            'Id' => 1,
            'Label' => 24000,
        ),
        5 => array(
            'Id' => 2,
            'Label' => 16000,
        ),
        6 => array(
            'Id' => 0,
            'Label' => 11025,
        ),
        7 => array(
            'Id' => 1,
            'Label' => 12000,
        ),
        8 => array(
            'Id' => 2,
            'Label' => 8000,
        ),
    );

    protected $Index = 'mixed';

}
