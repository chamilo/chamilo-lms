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
class Metering extends AbstractTag
{

    protected $Id = '6.1';

    protected $Name = 'Metering';

    protected $FullName = 'NikonCustom::SettingsD40';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Metering';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Matrix',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center-weighted',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
    );

}
