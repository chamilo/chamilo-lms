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
class FlashRemoteControl extends AbstractTag
{

    protected $Id = 1027;

    protected $Name = 'FlashRemoteControl';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Flash Remote Control';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Channel 1, Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Channel 2, Low',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Channel 3, Low',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Channel 4, Low',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Channel 1, Mid',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Channel 2, Mid',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Channel 3, Mid',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Channel 4, Mid',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Channel 1, High',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Channel 2, High',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Channel 3, High',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Channel 4, High',
        ),
    );

}
