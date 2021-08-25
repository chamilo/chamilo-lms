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
class CommandDialsApertureSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CommandDialsApertureSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Command Dials Aperture Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Sub-command Dial',
        ),
        1 => array(
            'Id' => 32,
            'Label' => 'Aperture Ring',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Sub-command Dial',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Aperture Ring',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Sub-command Dial',
        ),
        5 => array(
            'Id' => 32,
            'Label' => 'Aperture Ring',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Sub-command Dial',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'Aperture Ring',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Sub-command Dial',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Aperture Ring',
        ),
    );

}
