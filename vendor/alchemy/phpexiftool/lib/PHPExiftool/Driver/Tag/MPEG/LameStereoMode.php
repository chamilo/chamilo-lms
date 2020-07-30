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
class LameStereoMode extends AbstractTag
{

    protected $Id = 24;

    protected $Name = 'LameStereoMode';

    protected $FullName = 'MPEG::Lame';

    protected $GroupName = 'MPEG';

    protected $g0 = 'MPEG';

    protected $g1 = 'MPEG';

    protected $g2 = 'Audio';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Lame Stereo Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Mono',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Stereo',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Dual Channels',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Joint Stereo',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Forced Joint Stereo',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Auto',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Intensity Stereo',
        ),
    );

}
