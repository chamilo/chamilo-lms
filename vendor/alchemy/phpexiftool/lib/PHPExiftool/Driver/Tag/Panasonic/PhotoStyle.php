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
class PhotoStyle extends AbstractTag
{

    protected $Id = 137;

    protected $Name = 'PhotoStyle';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Photo Style';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard or Custom',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Vivid',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Natural',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Monochrome',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Scenery',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Portrait',
        ),
    );

}
