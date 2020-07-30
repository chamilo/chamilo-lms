<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageQuality2 extends AbstractTag
{

    protected $Id = 1539;

    protected $Name = 'ImageQuality2';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Image Quality 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'SQ',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'HQ',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'SHQ',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'RAW',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'SQ (5)',
        ),
    );

}
