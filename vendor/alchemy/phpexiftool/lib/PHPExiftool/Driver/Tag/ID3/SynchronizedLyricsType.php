<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ID3;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SynchronizedLyricsType extends AbstractTag
{

    protected $Id = 'type';

    protected $Name = 'SynchronizedLyricsType';

    protected $FullName = 'ID3::SynLyrics';

    protected $GroupName = 'ID3';

    protected $g0 = 'ID3';

    protected $g1 = 'ID3';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Synchronized Lyrics Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Other',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Lyrics',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Text Transcription',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Movement/part Name',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Events',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Chord',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Trivia/"pop-up" Information',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Web Page URL',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Image URL',
        ),
    );

}
