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
class CustomSettingsBank extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CustomSettingsBank';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Custom Settings Bank';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'A',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'B',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'C',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'D',
        ),
    );

}
