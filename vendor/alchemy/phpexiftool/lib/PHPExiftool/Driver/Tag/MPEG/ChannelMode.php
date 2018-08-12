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
class ChannelMode extends AbstractTag
{

    protected $Id = 'Bit24-25';

    protected $Name = 'ChannelMode';

    protected $FullName = 'MPEG::Audio';

    protected $GroupName = 'MPEG';

    protected $g0 = 'MPEG';

    protected $g1 = 'MPEG';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Channel Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Stereo',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Joint Stereo',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Dual Channel',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Single Channel',
        ),
    );

}
