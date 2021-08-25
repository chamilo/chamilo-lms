<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFAssistLamp extends AbstractTag
{

    protected $Id = 49;

    protected $Name = 'AFAssistLamp';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Assist Lamp';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Enabled but Not Used',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Disabled but Required',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Disabled and Not Required',
        ),
    );

}
