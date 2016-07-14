<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MovieShutterButton extends AbstractTag
{

    protected $Id = '38.3';

    protected $Name = 'MovieShutterButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Movie Shutter Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Take Photo',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'Record Movies',
        ),
        2 => array(
            'Id' => 32,
            'Label' => 'Live Frame Grab',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Take Photo',
        ),
        4 => array(
            'Id' => 32,
            'Label' => 'Record Movies',
        ),
    );

}
