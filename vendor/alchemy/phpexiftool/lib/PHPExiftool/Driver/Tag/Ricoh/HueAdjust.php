<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HueAdjust extends AbstractTag
{

    protected $Id = 4118;

    protected $Name = 'HueAdjust';

    protected $FullName = 'Ricoh::Main';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Hue Adjust';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Basic',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Magenta',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Yellow',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Normal',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Warm',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Cool',
        ),
    );

}
