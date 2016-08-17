<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class StreamType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'StreamType';

    protected $FullName = 'RIFF::StreamHeader';

    protected $GroupName = 'RIFF';

    protected $g0 = 'RIFF';

    protected $g1 = 'RIFF';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Stream Type';

    protected $MaxLength = 4;

    protected $Values = array(
        'auds' => array(
            'Id' => 'auds',
            'Label' => 'Audio',
        ),
        'iavs' => array(
            'Id' => 'iavs',
            'Label' => 'Interleaved Audio+Video',
        ),
        'mids' => array(
            'Id' => 'mids',
            'Label' => 'MIDI',
        ),
        'txts' => array(
            'Id' => 'txts',
            'Label' => 'Text',
        ),
        'vids' => array(
            'Id' => 'vids',
            'Label' => 'Video',
        ),
    );

}
