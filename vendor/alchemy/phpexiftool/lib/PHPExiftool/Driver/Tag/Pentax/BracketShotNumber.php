<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BracketShotNumber extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'BracketShotNumber';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Bracket Shot Number';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1 of 2',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1 of 3',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '1 of 5',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '2 of 2',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '2 of 3',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '2 of 5',
        ),
        35 => array(
            'Id' => 35,
            'Label' => '3 of 3',
        ),
        37 => array(
            'Id' => 37,
            'Label' => '3 of 5',
        ),
        53 => array(
            'Id' => 53,
            'Label' => '4 of 5',
        ),
        69 => array(
            'Id' => 69,
            'Label' => '5 of 5',
        ),
    );

}
