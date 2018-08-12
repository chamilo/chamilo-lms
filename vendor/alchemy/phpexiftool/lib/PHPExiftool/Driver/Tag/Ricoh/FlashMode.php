<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Auto, Fired',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'Auto, Fired, Red-eye reduction',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'Slow Sync',
        ),
        8 => array(
            'Id' => 5,
            'Label' => 'Manual',
        ),
        9 => array(
            'Id' => 6,
            'Label' => 'On, Red-eye reduction',
        ),
        10 => array(
            'Id' => 7,
            'Label' => 'Synchro, Red-eye reduction',
        ),
        11 => array(
            'Id' => 8,
            'Label' => 'Auto, Did not fire',
        ),
    );

}
