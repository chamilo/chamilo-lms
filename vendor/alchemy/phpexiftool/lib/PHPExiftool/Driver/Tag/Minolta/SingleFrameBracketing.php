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
class SingleFrameBracketing extends AbstractTag
{

    protected $Id = 33;

    protected $Name = 'SingleFrameBracketing';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Single Frame Bracketing';

    protected $flag_Permanent = true;

    protected $Values = array(
        770 => array(
            'Id' => 770,
            'Label' => 'Low',
        ),
        1794 => array(
            'Id' => 1794,
            'Label' => 'High',
        ),
    );

}
