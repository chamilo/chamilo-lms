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
class VideoEncoding extends AbstractTag
{

    protected $Id = 'Bit4-7';

    protected $Name = 'VideoEncoding';

    protected $FullName = 'Flash::Video';

    protected $GroupName = 'Flash';

    protected $g0 = 'Flash';

    protected $g1 = 'Flash';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Video Encoding';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'JPEG',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Sorensen H.263',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Screen Video',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'On2 VP6',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'On2 VP6 Alpha',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Screen Video 2',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'H.264',
        ),
    );

}
