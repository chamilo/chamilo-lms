<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashModel extends AbstractTag
{

    protected $Id = 4097;

    protected $Name = 'FlashModel';

    protected $FullName = 'Olympus::Equipment';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Flash Model';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'FL-20',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'FL-50',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'RF-11',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'TF-22',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'FL-36',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'FL-50R',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'FL-36R',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'FL-14',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'FL-600R',
        ),
    );

}
