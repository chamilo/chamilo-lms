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
class InternalFlashMode extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'InternalFlashMode';

    protected $FullName = 'Pentax::FlashInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Internal Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a - Off-Auto-Aperture',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'Fired, Wireless (Control)',
        ),
        149 => array(
            'Id' => 149,
            'Label' => 'Fired, Wireless (Master)',
        ),
        192 => array(
            'Id' => 192,
            'Label' => 'Fired',
        ),
        193 => array(
            'Id' => 193,
            'Label' => 'Fired, Red-eye reduction',
        ),
        194 => array(
            'Id' => 194,
            'Label' => 'Fired, Auto',
        ),
        195 => array(
            'Id' => 195,
            'Label' => 'Fired, Auto, Red-eye reduction',
        ),
        198 => array(
            'Id' => 198,
            'Label' => 'Fired, Wireless (Control), Fired normally not as control',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Fired, Slow-sync',
        ),
        201 => array(
            'Id' => 201,
            'Label' => 'Fired, Slow-sync, Red-eye reduction',
        ),
        202 => array(
            'Id' => 202,
            'Label' => 'Fired, Trailing-curtain Sync',
        ),
        240 => array(
            'Id' => 240,
            'Label' => 'Did not fire, Normal',
        ),
        241 => array(
            'Id' => 241,
            'Label' => 'Did not fire, Red-eye reduction',
        ),
        242 => array(
            'Id' => 242,
            'Label' => 'Did not fire, Auto',
        ),
        243 => array(
            'Id' => 243,
            'Label' => 'Did not fire, Auto, Red-eye reduction',
        ),
        244 => array(
            'Id' => 244,
            'Label' => 'Did not fire, (Unknown 0xf4)',
        ),
        245 => array(
            'Id' => 245,
            'Label' => 'Did not fire, Wireless (Master)',
        ),
        246 => array(
            'Id' => 246,
            'Label' => 'Did not fire, Wireless (Control)',
        ),
        248 => array(
            'Id' => 248,
            'Label' => 'Did not fire, Slow-sync',
        ),
        249 => array(
            'Id' => 249,
            'Label' => 'Did not fire, Slow-sync, Red-eye reduction',
        ),
        250 => array(
            'Id' => 250,
            'Label' => 'Did not fire, Trailing-curtain Sync',
        ),
    );

}
