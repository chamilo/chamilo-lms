<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Rotation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Rotation';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Rotation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 72,
            'Label' => 'Horizontal (normal)',
        ),
        1 => array(
            'Id' => 76,
            'Label' => 'Rotate 90 CW',
        ),
        2 => array(
            'Id' => 82,
            'Label' => 'Rotate 270 CW',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Horizontal (normal)',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Rotate 90 CW',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Rotate 270 CW',
        ),
        6 => array(
            'Id' => 72,
            'Label' => 'Horizontal (normal)',
        ),
        7 => array(
            'Id' => 76,
            'Label' => 'Rotate 90 CW',
        ),
        8 => array(
            'Id' => 82,
            'Label' => 'Rotate 270 CW',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Horizontal (Normal)',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Rotate 270 CW',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Rotate 90 CW',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Horizontal (normal)',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Rotate 270 CW',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Rotate 90 CW',
        ),
    );

}
