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
class AssistButtonFunction extends AbstractTag
{

    protected $Id = 13;

    protected $Name = 'AssistButtonFunction';

    protected $FullName = 'CanonCustom::Functions10D';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assist Button Function';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Select Home Position',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Select HP (while pressing)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Av+/- (AF point by QCD)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'FE lock',
        ),
    );

}
