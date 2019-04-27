<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Rotation extends AbstractTag
{

    protected $Id = '590.1';

    protected $Name = 'Rotation';

    protected $FullName = 'Nikon::ShotInfoD80';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Rotation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Horizontal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Rotated 270 CW',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Rotated 90 CW',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Rotated 180',
        ),
    );

}
