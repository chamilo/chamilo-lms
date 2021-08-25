<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MediaType extends AbstractTag
{

    protected $Id = 'stik';

    protected $Name = 'MediaType';

    protected $FullName = 'QuickTime::ItemList';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Media Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Movie',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Normal (Music)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Audiobook',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Whacked Bookmark',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Music Video',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Short Film',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'TV Show',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Booklet',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Ringtone',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Podcast',
        ),
    );

}
