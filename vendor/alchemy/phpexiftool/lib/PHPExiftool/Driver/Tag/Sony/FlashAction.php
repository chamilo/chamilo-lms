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
class FlashAction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashAction';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Action';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Did not fire',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'External Flash, Did not fire',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'External Flash, Fired',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Did not fire',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Flash Fired',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'External Flash Fired',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Did not fire',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Did not fire',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Did not fire',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
    );

    protected $Index = 'mixed';

}
