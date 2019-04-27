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
class FlashOptions extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'FlashOptions';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Options';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Red-eye reduction',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Auto',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Auto, Red-eye reduction',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Wireless (Master)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Wireless (Control)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Slow-sync',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Slow-sync, Red-eye reduction',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Trailing-curtain Sync',
        ),
    );

}
