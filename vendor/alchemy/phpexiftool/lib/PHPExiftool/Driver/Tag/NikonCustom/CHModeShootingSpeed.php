<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CHModeShootingSpeed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CHModeShootingSpeed';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'CH Mode Shooting Speed';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '9 fps',
        ),
        1 => array(
            'Id' => 16,
            'Label' => '10 fps',
        ),
        2 => array(
            'Id' => 32,
            'Label' => '11 fps',
        ),
        3 => array(
            'Id' => 0,
            'Label' => '10 fps',
        ),
        4 => array(
            'Id' => 16,
            'Label' => '11 fps',
        ),
    );

}
