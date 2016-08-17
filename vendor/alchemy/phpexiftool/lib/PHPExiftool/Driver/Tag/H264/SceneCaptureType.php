<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\H264;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SceneCaptureType extends AbstractTag
{

    protected $Id = 170;

    protected $Name = 'SceneCaptureType';

    protected $FullName = 'H264::MDPM';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Scene Capture Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Landscape',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Night',
        ),
    );

}
