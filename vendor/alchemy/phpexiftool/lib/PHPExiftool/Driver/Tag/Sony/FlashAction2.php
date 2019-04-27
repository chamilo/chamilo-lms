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
class FlashAction2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashAction2';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Action 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Fired, Autoflash',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Fired, Fill-flash',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Fired, Rear Sync',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Fired, Wireless',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Did not fire',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Fired, Slow Sync',
        ),
        6 => array(
            'Id' => 17,
            'Label' => 'Fired, Autoflash, Red-eye reduction',
        ),
        7 => array(
            'Id' => 18,
            'Label' => 'Fired, Fill-flash, Red-eye reduction',
        ),
        8 => array(
            'Id' => 34,
            'Label' => 'Fired, Fill-flash, HSS',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Did not fire',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'External Flash fired (2)',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'Built-in Flash fired',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'External Flash fired (4)',
        ),
    );

}
