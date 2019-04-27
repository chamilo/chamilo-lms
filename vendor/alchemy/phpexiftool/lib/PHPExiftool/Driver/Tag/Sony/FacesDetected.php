<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FacesDetected extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FacesDetected';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Faces Detected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => '-1',
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        2 => array(
            'Id' => 33,
            'Label' => 5,
        ),
        3 => array(
            'Id' => 57,
            'Label' => 2,
        ),
        4 => array(
            'Id' => 77,
            'Label' => 4,
        ),
        5 => array(
            'Id' => 93,
            'Label' => 3,
        ),
        6 => array(
            'Id' => 98,
            'Label' => 1,
        ),
        7 => array(
            'Id' => 115,
            'Label' => 8,
        ),
        8 => array(
            'Id' => 168,
            'Label' => 6,
        ),
        9 => array(
            'Id' => 241,
            'Label' => 7,
        ),
    );

}
