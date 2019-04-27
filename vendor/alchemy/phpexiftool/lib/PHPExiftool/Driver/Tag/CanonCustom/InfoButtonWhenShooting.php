<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InfoButtonWhenShooting extends AbstractTag
{

    protected $Id = 1033;

    protected $Name = 'InfoButtonWhenShooting';

    protected $FullName = 'CanonCustom::Functions2';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Info Button When Shooting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Displays camera settings',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Displays shooting functions',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Displays shooting functions',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Displays camera settings',
        ),
    );

    protected $Index = 'mixed';

}
