<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Matroska;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VideoScanType extends AbstractTag
{

    protected $Id = 26;

    protected $Name = 'VideoScanType';

    protected $FullName = 'Matroska::Main';

    protected $GroupName = 'Matroska';

    protected $g0 = 'Matroska';

    protected $g1 = 'Matroska';

    protected $g2 = 'Video';

    protected $Type = 'unsigned';

    protected $Writable = false;

    protected $Description = 'Video Scan Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Progressive',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Interlaced',
        ),
    );

}
