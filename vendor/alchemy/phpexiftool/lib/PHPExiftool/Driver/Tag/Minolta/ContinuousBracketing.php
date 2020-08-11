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
class ContinuousBracketing extends AbstractTag
{

    protected $Id = 32;

    protected $Name = 'ContinuousBracketing';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Continuous Bracketing';

    protected $flag_Permanent = true;

    protected $Values = array(
        771 => array(
            'Id' => 771,
            'Label' => 'Low',
        ),
        1795 => array(
            'Id' => 1795,
            'Label' => 'High',
        ),
    );

}
