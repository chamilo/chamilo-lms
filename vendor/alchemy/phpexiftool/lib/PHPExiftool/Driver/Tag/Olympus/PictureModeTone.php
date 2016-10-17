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
class PictureModeTone extends AbstractTag
{

    protected $Id = 1318;

    protected $Name = 'PictureModeTone';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Picture Mode Tone';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Neutral',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Sepia',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Blue',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Purple',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Green',
        ),
    );

}
