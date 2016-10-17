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
class VideoFieldOrder extends AbstractTag
{

    protected $Id = 'fiel';

    protected $Name = 'VideoFieldOrder';

    protected $FullName = 'QuickTime::ImageDesc';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Video Field Order';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Progressive',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2:1 Interlaced',
        ),
    );

}
