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
class MemoryCardConfiguration extends AbstractTag
{

    protected $Id = 22;

    protected $Name = 'MemoryCardConfiguration';

    protected $FullName = 'Sony::ExtraInfo3';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Memory Card Configuration';

    protected $flag_Permanent = true;

    protected $Values = array(
        244 => array(
            'Id' => 244,
            'Label' => 'MemoryStick in use, SD card present',
        ),
        245 => array(
            'Id' => 245,
            'Label' => 'MemoryStick in use, SD slot empty',
        ),
        252 => array(
            'Id' => 252,
            'Label' => 'SD card in use, MemoryStick present',
        ),
        254 => array(
            'Id' => 254,
            'Label' => 'SD card in use, MemoryStick slot empty',
        ),
    );

}
