<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FilmMode extends AbstractTag
{

    protected $Id = 66;

    protected $Name = 'FilmMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Film Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard (color)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Dynamic (color)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Nature (color)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Smooth (color)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Standard (B&W)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Dynamic (B&W)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Smooth (B&W)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Nostalgic',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Vibrant',
        ),
    );

}
