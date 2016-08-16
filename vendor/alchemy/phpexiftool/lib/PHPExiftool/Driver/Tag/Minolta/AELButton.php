<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AELButton extends AbstractTag
{

    protected $Id = 69;

    protected $Name = 'AELButton';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AEL Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Hold',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Toggle',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Spot Hold',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Spot Toggle',
        ),
    );

}
