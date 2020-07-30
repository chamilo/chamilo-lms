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
class AutoBracketingMode extends AbstractTag
{

    protected $Id = '12.3';

    protected $Name = 'AutoBracketingMode';

    protected $FullName = 'NikonCustom::SettingsD800';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Auto Bracketing Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Flash/Speed',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Flash/Speed/Aperture',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Flash/Aperture',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Flash Only',
        ),
    );

}
