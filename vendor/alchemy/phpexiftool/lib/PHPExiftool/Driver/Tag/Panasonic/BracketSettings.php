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
class BracketSettings extends AbstractTag
{

    protected $Id = 69;

    protected $Name = 'BracketSettings';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Bracket Settings';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Bracket',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '3 Images, Sequence 0/-/+',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '3 Images, Sequence -/0/+',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '5 Images, Sequence 0/-/+',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '5 Images, Sequence -/0/+',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '7 Images, Sequence 0/-/+',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '7 Images, Sequence -/0/+',
        ),
    );

}
