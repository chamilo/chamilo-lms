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
class DriveMode2 extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'DriveMode2';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Drive Mode 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single-frame',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Continuous (Lo)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Self-timer (12 s)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Self-timer (2 s)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Remote Control (3 s delay)',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Remote Control',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Exposure Bracket',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Multiple Exposure',
        ),
    );

}
