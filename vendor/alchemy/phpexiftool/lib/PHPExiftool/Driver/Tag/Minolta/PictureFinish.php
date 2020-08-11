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
class PictureFinish extends AbstractTag
{

    protected $Id = 113;

    protected $Name = 'PictureFinish';

    protected $FullName = 'Minolta::CameraSettings5D';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Picture Finish';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Natural',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Natural+',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Wind Scene',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Evening Scene',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Night Scene',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Night Portrait',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Monochrome',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Adobe RGB',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Adobe RGB (ICC)',
        ),
    );

}
