<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SpecialEffectSetting extends AbstractTag
{

    protected $Id = 12337;

    protected $Name = 'SpecialEffectSetting';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Special Effect Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Makeup',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Mist Removal',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Vivid Landscape',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Art Shot',
        ),
    );

}
