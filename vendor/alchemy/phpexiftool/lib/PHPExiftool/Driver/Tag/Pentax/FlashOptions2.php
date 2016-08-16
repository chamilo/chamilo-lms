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
class FlashOptions2 extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'FlashOptions2';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Options 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Red-eye reduction',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Auto',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Auto, Red-eye reduction',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Wireless (Master)',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Wireless (Control)',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Slow-sync',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'Slow-sync, Red-eye reduction',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Trailing-curtain Sync',
        ),
    );

}
