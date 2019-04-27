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
class LCDPanels extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'LCDPanels';

    protected $FullName = 'CanonCustom::Functions1D';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Top/Back LCD Panels';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Remain. shots/File no.',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'ISO/Remain. shots',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'ISO/File no.',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Shots in folder/Remain. shots',
        ),
    );

}
