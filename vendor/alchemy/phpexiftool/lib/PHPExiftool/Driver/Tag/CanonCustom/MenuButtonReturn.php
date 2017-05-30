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
class MenuButtonReturn extends AbstractTag
{

    protected $Id = 11;

    protected $Name = 'MenuButtonReturn';

    protected $FullName = 'CanonCustom::FunctionsD30';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Menu Button Return';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Top',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Previous (volatile)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Previous',
        ),
    );

}
