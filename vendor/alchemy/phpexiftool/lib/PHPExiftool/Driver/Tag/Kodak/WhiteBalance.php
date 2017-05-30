<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Kodak;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalance extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WhiteBalance';

    protected $FullName = 'mixed';

    protected $GroupName = 'Kodak';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Kodak';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Flash?',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Tungsten',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Daylight',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Daylight',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Flash',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'Tungsten',
        ),
    );

}
